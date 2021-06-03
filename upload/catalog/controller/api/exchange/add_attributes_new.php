<?php

/**
 * Class ControllerApiExchangeAddAttributes
 *
 * Adding attributes to product
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * product_id - Int
 * codeGroup - Int
 * attributes - Array of JSON [{'name':'Производитель', 'property':'Electrolux'}]
 */

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

class ControllerApiExchangeAddAttributes extends Controller {

    /**
     * Adding attributes to product
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

            foreach ($results as $result) {
                $ip_data[] = trim($result['ip']);
            }

            if (!in_array($this->request->server['REMOTE_ADDR'], $ip_data)) {
                $json['error']['ip'] = sprintf($this->language->get('error_ip'), $this->request->server['REMOTE_ADDR']);
            }else{
                $this->load->model('api/exchange/products');
                $this->load->model('api/exchange/manufacturer');
                $this->load->model('api/exchange/common');
                $this->load->model('api/exchange/ocfilter');
                $this->load->model('api/exchange/attribute');

                $json['success'] = sprintf($this->language->get('error'));

                if (isset($this->request->post['product_attributes'])) {
                    $product_attributes = $_POST['product_attributes'];

//                    $this->log->write('attr: '.print_r($product_attributes, true));

                    $language_id = $this->model_api_exchange_common->getLanguageIdByCode('ru-ru');

                    foreach (json_decode($product_attributes) as $product){
                        $attributes = $product->attributes;
                        $this->model_api_exchange_attribute->deleteAttributes($product->product_id);

                        if( is_array($attributes) && count($attributes) > 0 ){
                            $i = 0;
                            foreach ( ($attributes) as $attribute ){
                                if($attribute->name == 'Производитель'){
                                    $manufacturer_id = $this->model_api_exchange_manufacturer->
                                    getManufacturerIdByName($attribute->property);

                                    if( $manufacturer_id == 0 ){
                                        $manufacturer_id = $this->model_api_exchange_manufacturer->
                                        addManufacturer($this->cutSymbols($attribute->property));
                                    }else {
                                        $this->model_api_exchange_products->updateManufacturerProduct($product->product_id, $manufacturer_id);
                                    }
                                }

                                $option_keyword = $this->model_api_exchange_common1->cyrToLat($attribute->name);
                                $option_id = $attribute->attribute_id;//$this->model_api_exchange_ocfilter->isOptionExist($option_keyword);
                                $attribute_id = $attribute->attribute_id;
                                $data_options = array(
                                    'type' => 'checkbox',
                                    'keyword' => $option_keyword,
                                    'status' => 1,
                                    'language_id' => $language_id,
                                    'name' => $this->cutSymbols($attribute->name),
                                    'option_id' => $option_id,
                                    'codeGroup' => $product->codeGroup,
                                    'index_attribute' => property_exists($attribute,'index_attribute') ? $attribute->index_attribute : 999,

                                );
                                if ($data_options['index_attribute'] == 999) {
                                    unset($data_options['index_attribute']);
                                }
                                $json_options = json_encode($data_options);
                                $data_options = json_decode($json_options);


                                if(!$this->model_api_exchange_ocfilter->isOptionExistById($option_id)){
                                    $this->model_api_exchange_ocfilter->addOption($data_options);
                                    $this->model_api_exchange_ocfilter->addOptionDescription($data_options);
                                    $this->model_api_exchange_ocfilter->addOptionToCategory($option_id, $product->codeGroup);
                                }else{
                                    $this->model_api_exchange_ocfilter->updateOption($data_options);
                                    $this->model_api_exchange_ocfilter->addOptionToCategory($option_id, $product->codeGroup);
                                }
                                // $this->log->write('group: '.$product->codeGroup.', product: '.$product->product_id);

                                if(!$this->model_api_exchange_attribute->isAttributeGroupExist($product->codeGroup)){ //if group not found - add it
                                    $this->model_api_exchange_attribute->addGroupAttribute($data_options);
                                }


                                $value_keyword = $this->model_api_exchange_common->cyrToLat($attribute->property);
                                $value_id = $this->model_api_exchange_ocfilter->isValueExist($value_keyword);
                                $data_value = array(
                                    'option_id' => $option_id,
                                    'keyword' => $value_keyword,
                                    'language_id' => $language_id,
                                    'name' => $this->cutSymbols($attribute->property),
                                    'product_id' => $product->product_id,
                                    'sort_order' => $i++,
                                    'value_id' => $value_id
                                );
                                $json_values = json_encode($data_value);
                                $data_value = json_decode($json_values);

                                if(!$value_id){
                                    $data_value->value_id = $value_id = $this->model_api_exchange_ocfilter->addOptionValue($data_value);
                                    $this->model_api_exchange_ocfilter->addOptionValueToProduct($data_value);
                                }{
                                    $this->model_api_exchange_ocfilter->addOptionValueToProduct($data_value);
                                }

//                                if(!$this->model_api_exchange_ocfilter->isValueExist($value_id)){
//                                    $this->model_api_exchange_ocfilter->addOptionValue($data_value);
//                                    $this->model_api_exchange_ocfilter->addOptionValueToProduct($data_value);
//                                }{
//                                    $this->model_api_exchange_ocfilter->updateOptionValue($data_value);
//                                    $this->model_api_exchange_ocfilter->addOptionValueToProduct($data_value);
//                                }

//                                $attribute_name = $this->cutSymbols($attribute->name);

                                if(!$this->model_api_exchange_attribute->isAttributeExist($attribute_id, $product->codeGroup)){ //if attribute not found - add it
                                    $this->model_api_exchange_attribute->addAttribute($data_options);
                                }
                                $this->model_api_exchange_attribute->addAttributeToProduct($attribute_id, $product->product_id, $this->cutSymbols($attribute->property), $language_id);

                            }
                        }
                    }
                    $this->model_api_exchange_common->deleteCacheFiles('/home/h63053/data/www/storage_optovik_shop/cache',
                        '[^cache.ocfilter.option*]');
                    $this->model_api_exchange_common->deleteCacheFiles('/home/h63053/data/www/storage_optovik_shop/cache',
                        '[^cache.ocfilter.manufacturer*]');

//                    $json['success'] = sprintf($this->language->get('success'));
                    $json['success'] = var_dump(json_decode($product_attributes));
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function cutSymbols($str){
//        $str = str_replace('\'','&apos;',$str);
//        $str = str_replace('"','&quot;',$str);

        return $str;
    }


}

