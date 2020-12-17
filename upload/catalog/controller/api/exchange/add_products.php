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
 * status - Int -- visibility of the product (1 - visible, 0 - not)
 *
 * -------------
 * Stickers, meta_description, meta_keywords, related, reward, special - will not be a overwritten
 */


class ControllerApiExchangeAddProducts extends Controller {
    private $imagePath = 'img/';
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
                $json['success'] = sprintf($this->language->get('error'));

                if (isset($this->request->post['products'])) {

                    $products = $_POST['products'];
//                    $this->model_api_exchange_products->hideAllProducts();
                    $language_id = $this->model_api_exchange_common->getLanguageIdByCode('ru-ru');

                    //Main loop (for each product)
                    foreach (json_decode($products) as $product){
//                        $this->log->write($product->name);
                        $keyword = addslashes($this->model_api_exchange_common->cyrToLat($product->name));
                        $query = 'product_id='.$product->code;
                        $img = '';
                        if (isset($product->mainImage)) $img = $this->imagePath . $product->mainImage;
                        $arr = array(
                            'product_id' => $product->code,
                            'model' => $product->code,
                            'name' => addslashes($product->name),
                            'image' => $img,
//                            'stock_status_id' => 5, //$this->getStockStatus($product), //Status, after quantity < 1
                            'category_id' => $product->codeGroup,
                            'language_id' => $language_id,
                            'store_id' => 0,
                            'layout_id' => 0,
                            'keyword' => $keyword,
                            'query' => $query,
                            'status' => $product->status,
                            'length' => $product->length,
                            'height' => $product->height,
                            'width' => $product->width
                        );
                        $data = json_encode($arr);
                        $data = json_decode($data);

                        if( $this->model_api_exchange_products->isProductExist($product->code) ){//Product exist- update
                            $this->model_api_exchange_products->updateProduct($data);
                            $this->model_api_exchange_products->updateProductDescription($data);
                            $this->model_api_exchange_common->updateSeoUrl($data);
                        } else {
                            $this->model_api_exchange_products->addProduct($data);
                            $this->model_api_exchange_products->addProductDescription($data);
                            $this->model_api_exchange_common->addSeoUrl($data);
                        }

                        $this->model_api_exchange_products->insertProductToCategory($product->code, $product->codeGroup);
                        $this->model_api_exchange_products->insertProductToLayout($product->code);
                        $this->model_api_exchange_products->insertProductToStore($product->code);

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


}