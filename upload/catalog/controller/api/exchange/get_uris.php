<?php
/**
 * Class ControllerApiExchangeGetUris
 *
 *  Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * Returned array of URI's for the all goods
 */

class ControllerApiExchangeGetUris extends Controller {

    private $listOfIds = array();
    /**
     * Getting all uri's from products table
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
                $this->load->model('api/exchange/uris');
                $json['success'] = sprintf($this->language->get('error'));

                $tableRow = array();
                
                $result = $this->model_api_exchange_uris->getAllProducts();
                if ($result->num_rows > 0) {
                    foreach($result->rows as $row) {
                        array_push($this->listOfIds, $row['product_id']);
                        $category_id = $row['category_id'];
                        array_push($this->listOfIds, $category_id);
                        $this->getParentCategories($category_id);
                        $aliases = $this->getAliases();
                        array_push($tableRow, array( 
                            'product_id' => $row['product_id'],
                            'path' => $aliases
                        ));
                        $this->listOfIds = [];
                    }
                }
                $json['links'] = $tableRow;
                $json['success'] = sprintf($this->language->get('success'));
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Getting aliases for ID's
     */
    private function getAliases()
    {
        $aliases = 'https://optovik.shop/';
        $y = array_reverse($this->listOfIds);
        foreach($y as $id){
            $aliases .= $this->model_api_exchange_uris->getSeoAlias($id);
            $aliases .= '/';
        }
        return $aliases;
    }

    /**
     * Getting ID's of the all nested categories
     * @param int
     */
    private function getParentCategories($category_id)
    {
        $parent_id = $this->model_api_exchange_uris->getParentId($category_id);
        if($parent_id != 0) {
            array_push($this->listOfIds, $parent_id);
            $this->getParentCategories($parent_id, $this->listOfIds);
        }
    }

}

