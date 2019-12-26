<?php

/**
 * Class ControllerApiExchangeGetImages
 *
 *  Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * Returned array of JSON's
 */

class ControllerApiExchangeGetImages extends Controller {

    /**
     * Getting all images and hash from images table
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
                $this->load->model('api/exchange/images');

                $tableRow = array();
                $result = $this->model_api_exchange_images->getAllImages();
                if ($result->num_rows > 0) {
                    foreach($result->rows as $row) {
                        array_push($tableRow, $row);
                    }
                }
                $json['images'] = json_encode($tableRow);
                $json['success'] = sprintf($this->language->get('success'));
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

//                $p = dirname($this->request->server['DOCUMENT_ROOT']);
//                $json['text'] = $p.'/image/img/';

}

