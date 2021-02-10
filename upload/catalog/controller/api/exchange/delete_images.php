<?php

/**
 * Class ControllerApiExchangeDeleteImages
 *
 * Deleting images
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * images - Array of JSON [{'name':'59876a.jpg'},{'name':'56132b.jpg'}]
 */

class ControllerApiExchangeDeleteImages extends Controller {

    /**
     * Deleting images
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
                $this->load->model('api/exchange/images');
                $json['success'] = sprintf($this->language->get('error'));

                if (isset($this->request->post['images'])) {
                    $images = $_POST['images'];
                    $sss = [];
                    foreach (json_decode($images) as $image) {
                        if (isset($image->name) && strlen($image->name) > 3) {
                            $this->model_api_exchange_images->deleteImageByName($image->name);
                            $this->model_api_exchange_images->deleteProductImageByName($image->name);
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

