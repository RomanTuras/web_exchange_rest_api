<?php

/**
 * Class ControllerApiExchangeAddProductPrices
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * `productPrices` - array of JSON's, which with fields:
 * product_id - Int
 * price_id - Int
 * price_value - Int
 * price_special_value - Int
 * special_date_end (String) date in format '2019-12-28'
 */


class ControllerApiExchangeAddProductPrices extends Controller {

    /**
     * Add types of prices to product
     */
    public function index(){
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
                $this->load->model('api/exchange/prices');
                $this->load->model('api/exchange/products');
                $json['success'] = sprintf($this->language->get('error'));

                if (isset($this->request->post['productPrices'])) {
                    $productPrices = $_POST['productPrices'];
//                    $this->log->write(print_r($productPrices, true));

                    $json['success'] = sprintf($this->language->get('error'));

                    foreach (json_decode($productPrices) as $item){
                        $price_special_value = isset($item->price_special_value) ? $item->price_special_value : 0;
                        $special_date_end = isset($item->special_date_end) ? $item->special_date_end : '0000-00-00';
                        $arr = array(
                            'product_id' => $item->product_id,
                            'price_id' => $item->price_id,
                            'price_value' => $item->price_value,
                            'price_special_value' => $price_special_value,
                            'special_date_end' => isset($item->special_date_end) ? $item->special_date_end : "2065-01-08",
                            'customer_group_id' => 0
                        );
                        $data = json_encode($arr);
                        $data = json_decode($data);

                        if($item->price_id == 4 && $item->price_value > 0){//Розница
//                            if($this->model_api_exchange_prices->isProductExist($item->product_id)){
                            $this->model_api_exchange_prices->updateProductPrice($item->product_id, $item->price_value);
//                            }
                        }
                        $price_type_in_product = $this->model_api_exchange_prices->isPriceTypeExistInProduct($item->product_id, $item->price_id);

                        if ($item->price_value != 0) {
                            if($price_type_in_product){
                                $this->model_api_exchange_prices->updatePriceTypeInProduct($data);
                            } else {
                                $this->model_api_exchange_prices->addProductPrice($data);
                            }
                        } else {
                            $this->model_api_exchange_prices->deleteDiscount($data);
                        }


                        $data->customer_group_id = $item->price_id;
                        if($price_special_value > 0){
                            if($this->model_api_exchange_prices->isSpecialExistInProduct($item->product_id, $item->price_id)){
                                $this->model_api_exchange_prices->updateSpecial($data);
                            }else{
                                $this->model_api_exchange_prices->addSpecial($data);
                            }
                        }else{
                            if($this->model_api_exchange_prices->isSpecialExistInProduct($item->product_id, $item->price_id)){
                                $this->model_api_exchange_prices->deleteSpecial($data);
                            }
                        }

                        //Checking, is main price exist and > 0
                        $main_price = $this->model_api_exchange_prices->getPrice($item->product_id);
                        //Update main price from price types repository
                        if($main_price == 0){
                            $row = $this->model_api_exchange_prices->getPriceProductByType($item->product_id, 4);
                            if($row) {
                                $price = $row['price_value'];
                                if($price > 0){
                                    $this->model_api_exchange_prices->updatePrice($item->product_id, $price);
                                }
                            }
                        }

                        $this->model_api_exchange_products->hideZeroProductsBalances();
                        $json['success'] = sprintf($this->language->get('success'));
                    }
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}

