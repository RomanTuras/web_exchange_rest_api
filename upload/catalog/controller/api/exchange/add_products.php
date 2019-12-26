<?php

/**
 * Class ControllerApiExchangeAddProducts
 *
 * Before exchange, all products are reset to 0 quantity and "Out of stock" status
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * `products` - array of JSON's, which with fields:
 * code - Int
 * name - String
 * codeGroup - Int
 * description - String
 * mainImage - String
 * regularPrice - Int
 * freeRemains - Int
 * availableToOrder - Int
 * vendorAvailability - Boolean
 * images - Array of JSON [{'image':'59876a.jpg'},{'image':'56132b.jpg'}]
 * attributes - Array of JSON [{'name':'Производитель', 'property':'Electrolux'},{'name':'Покрытие чаши', 'property':'Антипригарное'}]
 *
 * -------------
 * Stickers, meta_description, meta_keywords, related, reward, special - will not be a overwritten
 */

class ControllerApiExchangeAddProducts extends Controller {
    private $imagePath = 'img/';
//TODO replace all symbols " to
    /**
     * Adding products to DB
     * Parameters passed through POST
     */
    public function index() {
        $this->load->language('api/exchange/web_exchange');
        $this->load->model('account/api');

        unset($this->session->data['web_exchange']);

        $json = array();

        // Login with API Key
        if(isset($this->request->post['username'])) {
            $api_info = $this->model_account_api->login($this->request->post['username'], $this->request->post['key']);
        } else {
            $api_info = $this->model_account_api->login('Default', $this->request->post['key']);
        }

        $json['success'] = sprintf($this->language->get('error'));
        if ($api_info) {
            // Check if IP is allowed
            $ip_data = array();
            $results = $this->model_account_api->getApiIps($api_info['api_id']);

            foreach ($results as $result_category) {
                $ip_data[] = trim($result_category['ip']);
            }

            if (!in_array($this->request->server['REMOTE_ADDR'], $ip_data)) {
                $json['error']['ip'] = sprintf($this->language->get('error_ip'), $this->request->server['REMOTE_ADDR']);
            }else{
                $this->load->model('api/exchange/products');
                $this->load->model('api/exchange/common');
                $this->load->model('api/exchange/manufacturer');
                $this->load->model('api/exchange/ocfilter');

                if (isset($this->request->post['products'])) {

                    $products = $_POST['products'];
                    $this->model_api_exchange_products->hideAllProducts();
                    $language_id = $this->model_api_exchange_common->getLanguageIdByCode('ru-ru');

                    //Main loop (for each product)
                    foreach (json_decode($products) as $product){
                        $keyword = $this->model_api_exchange_common->cyrToLat($product->name);
                        $query = 'product_id='.$product->code;
                        $quantity = $product->freeRemains > 0 ? $product->freeRemains : $product->availableToOrder;
                        $arr = array(
                            'product_id' => $product->code,
                            'model' => $product->code,
                            'name' => $product->name,
                            'description' => $this->cutStyleTagsFromHead($product->description),
                            'image' => $this->imagePath . $product->mainImage,
                            'quantity' => $quantity,
                            'price' => $product->regularPrice,
                            'stock_status_id' => 5, //$this->getStockStatus($product), //Status, after quantity < 1
                            'category_id' => $product->codeGroup,
                            'language_id' => $language_id,
                            'store_id' => 0,
                            'layout_id' => 0,
                            'keyword' => $keyword,
                            'query' => $query
                        );
                        $data = json_encode($arr);
                        $data = json_decode($data);

                        $this->model_api_exchange_products->deleteImages($product->code);
                        if( $this->model_api_exchange_products->isProductExist($product->code) ){//Product exist- update
                            $this->model_api_exchange_products->updateProduct($data);
                            $this->model_api_exchange_products->updateProductDescription($data);
                            $this->model_api_exchange_common->updateSeoUrl($data);
                        } else {
                            $this->model_api_exchange_products->addProduct($data);
                            $this->model_api_exchange_products->addProductDescription($data);
                            $this->model_api_exchange_common->addSeoUrl($data);
                        }
                        $this->addImages($product);
                        $this->model_api_exchange_products->insertProductToCategory($product->code, $product->codeGroup);
                        $this->model_api_exchange_products->insertProductToLayout($product->code);
                        $this->model_api_exchange_products->insertProductToStore($product->code);
                        $this->addAttributes($product);
                    }//End Main Loop

                    $json['success'] = sprintf($this->language->get('success'));
                }else $json['success'] = sprintf($this->language->get('error'));

                $this->model_api_exchange_common->deleteCacheFiles('/home/h63053/data/www/storage_optovik_shop/cache',
                    '[^cache.ocfilter*]');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Loop for add additional images
     * @param $product
     */
    function addImages($product){
        $images = $product->images;
        if( is_array(json_decode($images)) ){
            $i = 0;
            foreach ( json_decode($images) as $obj ){
                $data_image = array(
                    'product_id' => $product->code,
                    'sort_order' => $i++,
                    'image' => $this->imagePath . $obj->image
                );
                $json = json_encode($data_image);
                $data = json_decode($json);
                $this->model_api_exchange_products->addImage($data);
            }
        }
    }

    /**
     * Adding options and options values to product
     * @param $product
     */
    private function addAttributes($product){
        $attributes = $product->attributes;
        if( is_array(json_decode($attributes)) ){
            $language_id = $this->model_api_exchange_common->getLanguageIdByCode('ru-ru');
            $this->model_api_exchange_ocfilter->deleteOptionValueFromProduct($product->code);
            $i = 0;
            foreach ( json_decode($attributes) as $attribute ){
                //$this->log->write('attr: '.$attribute->name);
                if( $attribute->name == 'Производитель' ) {
                    $manufacturer_id = $this->model_api_exchange_manufacturer->
                    getManufacturerIdByName($attribute->property);

                    if( $manufacturer_id == 0 ){
                        $manufacturer_id = $this->model_api_exchange_manufacturer->addManufacturer($attribute->property);
                    }
                    $this->model_api_exchange_products->updateManufacturerProduct($product->code, $manufacturer_id);
                } else {//add option if not exist, checking by option_id and keyword
                    if( !$this->model_api_exchange_ocfilter->
                    isOptionExist($product->codeGroup, $this->model_api_exchange_common->cyrToLat($attribute->name) ) ){
                        $data_options = array(
                            'option_id' => $product->codeGroup,
                            'type' => 'checkbox',
                            'keyword' => $this->model_api_exchange_common->cyrToLat($attribute->name),
                            'status' => 1,
                            'language_id' => $language_id,
                            'name' => $attribute->name
                        );
                        $json_options = json_encode($data_options);
                        $data_options = json_decode($json_options);
                        $this->model_api_exchange_ocfilter->addOption($data_options);
                        $this->model_api_exchange_ocfilter->addOptionDescription($data_options);
                        $this->model_api_exchange_ocfilter->addOptionToCategory($product->codeGroup);
                    }

                    $keyword = $this->model_api_exchange_common->cyrToLat($attribute->property);
                    $result = $this->model_api_exchange_ocfilter->getOptionValueId($product->codeGroup, $keyword);
                    $value_id = 0;
                    if ($result->num_rows > 0) {//If value already exist - add them to product
                        foreach($result->rows as $row) {
                            $value_id = $row['value_id'];
                        }
                        $this->model_api_exchange_ocfilter->
                        addOptionValueToProduct($product->code, $product->codeGroup, $value_id);
                    } else {//If value not found - insert in and add to product
                        $data_value = array(
                            'option_id' => $product->codeGroup,
                            'keyword' => $keyword,
                            'language_id' => $language_id,
                            'name' => $attribute->property,
                            'product_id' => $product->code,
                            'sort_order' => $i++
                        );
                        $json_values = json_encode($data_value);
                        $data_value = json_decode($json_values);
                        $this->model_api_exchange_ocfilter->addOptionValue($data_value);
                    }
                }
            }
        }

    }

    /**
     * Getting stock status, depends on product availability
     * @param $product object JSON
     * @return int
     */
    private function getStockStatus($product){
        //Stock statuses
        $inStock = 7;
        $outOfStock = 5;
        $preOrder = 8;
        $expected = 6;
        if ( $product->freeRemains > 0 ) return $inStock;
        elseif ( $product->availableToOrder > 0 ) return $expected;
        elseif ( $product->vendorAvailability ) return $preOrder;
        else return $outOfStock;
    }

    /**
     * Cutting style descriptions from header
     * @param $str
     * @return mixed
     */
    private function cutStyleTagsFromHead($str){
        $str = str_replace('\'','&apos;',$str);
        $str = str_replace('"','&quot;',$str);
        $str = str_replace('&','&amp;',$str);
        return preg_replace('/<style([^&]*)style>/', '', $str);
    }

}