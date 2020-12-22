<?php

/**
 * Class ControllerApiExchangeAddImages
 *
 * Adding additional images to product
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * product_id - Int
 * images - Array of JSON [{'name':'59876a.jpg'},{'name':'56132b.jpg'}]
 */

class ControllerApiExchangeAddImages extends Controller {

    /**
     * Adding additional images to product
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
                $json['success'] = sprintf($this->language->get('error'));

                if (isset($this->request->post['product_images'])) {
                    $product_images = $_POST['product_images'];

                    foreach (json_decode($product_images) as $product){
                        $images = $product->images;
                        $this->model_api_exchange_products->deleteImages($product->product_id);
                        if( is_array($images) && count($images) > 0 ){
                            $i = 0;
                            foreach ( $images as $obj ){
                                $imagePath = '';
                                if (isset($obj->name) && strlen($obj->name) > 3) $imagePath = 'img/' . $obj->name;
                                $data_image = array(
                                    'product_id' => (int)$product->product_id,
                                    'sort_order' => $i++,
                                    'image' => $imagePath
                                );
                                $json_images = json_encode($data_image);
                                $data = json_decode($json_images);
                                $this->model_api_exchange_products->addImage($data);
                            }
                        }
                    }
                    $json['success'] = sprintf($this->language->get('success'));
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}

