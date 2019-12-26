<?php

/**
 * Class ControllerApiExchangeAddImages
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * `images` - array of JSON's, which with fields:
 * name - String
 * hash - String
 */

class ControllerApiExchangeAddImages extends Controller {

    /**
     * Adding images to DB
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

                if (isset($this->request->post['images'])) {
                    $images = $_POST['images'];
                    foreach (json_decode($images) as $image){
                        if ( $this->model_api_exchange_images->isImagePresent($image->name) ){
                            $this->model_api_exchange_images->updateImageHashByName($image->name, $image->hash);
                        } else {
                            $this->model_api_exchange_images->insertImage($image->name, $image->hash);
                        }
                    }
                    $json['success'] = sprintf($this->language->get('success'));
                    $this->checkImages();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Checking images from database and on the disk,
     * if image not found from folder - deleting it from table (DB)
     * Put result in to log file
     */
    private function checkImages(){
        $path_to_log_images = dirname(__DIR__, 4);
        $log_images = $path_to_log_images.'/admin/controller/extension/module/log_images.hlp';
        if(file_exists($log_images))unlink($log_images);
        $path_to_images = dirname(__DIR__, 4).'/image/img/';
        $result = $this->model_api_exchange_images->getAllImages();
        if ($result->num_rows > 0) {
            $i = 0;
            foreach($result->rows as $row) {
                if(!file_exists($path_to_images.$row['img_name'])){
                    $filename = $row['img_name'];
                    $i++;
                    $this->writeFile($log_images, $filename);
                    $this->model_api_exchange_images->deleteImageByName($filename);
                }
            }
        }
    }

    /**
     * Written log to file
     * @param $filename String
     * @param $text String
     */
    function writeFile($filename, $text){
        file_put_contents($filename, $text."\r\n", FILE_APPEND);
    }
}

