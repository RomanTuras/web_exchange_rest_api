<?php

/**
 * Class ControllerApiExchangeAddDescriptions
 *
 * Adding Descriptions to product
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * product_id - Int
 * descriptions - String
 */

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
class ControllerApiExchangeAddDescriptions extends Controller {

    /**
     * Adding Descriptions to product
     * Parameters passed through POST
     */
    public function index() {
        $this->load->language('api/exchange/web_exchange');
        $this->load->model('account/api');

        unset($this->session->data['web_exchange']);

//        $text_to_file = 'two - '.print_r($this->request->post, true);
//        file_put_contents(dirname(__FILE__).'/errors.txt', $text_to_file."\r\n", FILE_APPEND);

        $json = array();

        // Login with API Key
        if(isset($this->request->post['username'])) {
            $api_info = $this->model_account_api->login($this->request->post['username'], $this->request->post['key']);
        } else {
            $api_info = $this->model_account_api->login('Default', $this->request->post['key']);
        }

        if ($api_info) {
            // Check if IP is allowed
            $ip_data = array();
            $results = $this->model_account_api->getApiIps($api_info['api_id']);

            foreach ($results as $result) {
                $ip_data[] = trim($result['ip']);
            }

            if (!in_array($this->request->server['REMOTE_ADDR'], $ip_data)) {
//                if(!true){
                $json['error']['ip'] = sprintf($this->language->get('error_ip'), $this->request->server['REMOTE_ADDR']);
            }else{
                $this->load->model('api/exchange/products');

                $json['success'] = sprintf($this->language->get('error'));

                if (isset($this->request->post['product_descriptions'])) {
                    $product_descriptions = $_POST['product_descriptions'];

                    foreach (json_decode($product_descriptions) as $product){
                        $text = $this->cutStyleTagsFromHead($product->description);
//                        $this->log->write($product->product_id.'<br>'.$text.'<br>'.'<br>'.'***********<br>');
                        $this->model_api_exchange_products->
                        updateOnlyProductDescription($product->product_id, $text);
                    }

                    $json['success'] = sprintf($this->language->get('success'));
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Cutting style descriptions from header
     * @param $str
     * @return mixed
     */
    private function cutStyleTagsFromHead($str){
        $str = preg_replace('/<html>/','',$str);
        $str = preg_replace('/<\/html>/','',$str);
        $str = preg_replace('/<body>/','',$str);
        $str = preg_replace('/<\/body>/','',$str);
        $str = preg_replace('/<a[\S\s]*?<\/a>/', '', $str);
        $str = preg_replace('/<head[\S\s]*?head>/', '', $str);
        $str = preg_replace('/style=&quot;[\S\s]*?&quot;/', '', $str);
        $str = preg_replace('/style=[\S\s]*?>/', '>', $str);
        $str = preg_replace('/<span[\S\s]*?>/', '', $str);
        $str = addslashes($str);
        $str = preg_replace('/<\/span>/','',$str);
        $str = preg_replace('/<style([^&]*)style>/', '', $str);
        return $str;
    }

}

