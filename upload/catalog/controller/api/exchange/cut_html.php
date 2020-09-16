<?php

/**
 * Class ControllerApiExchangeCutHtml
 *
 *  Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 * `text` - text with html tags
 *
 * Returned text
 */

class ControllerApiExchangeCutHtml extends Controller {

    private $listOfIds = array();
    /**
     * Cutting particular html tags from text
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
        // $api_info= true; // ----
        if ($api_info) {
            // Check if IP is allowed
            $ip_data = array();
            $results = $this->model_account_api->getApiIps($api_info['api_id']);

            foreach ($results as $result) {
                $ip_data[] = trim($result['ip']);
            }

            if (!in_array($this->request->server['REMOTE_ADDR'], $ip_data)) {
            // if(!true){
                $json['error']['ip'] = sprintf($this->language->get('error_ip'), $this->request->server['REMOTE_ADDR']);
            }else{
                $json['success'] = sprintf($this->language->get('error'));

                $text = 'empty';

                if (isset($this->request->post['text'])) {
                    $text = $this->removeHtmlHeaders($_POST['text']);
                }

                $json['text'] = $text;
                $json['success'] = sprintf($this->language->get('success'));
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Removing HTML headers
     * @param $str
     * @return string
     */
    private function removeHtmlHeaders($str)
    {
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