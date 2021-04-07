<?php

/**
 * Class ControllerApiExchangeCustomers
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * `update` - array of JSON's, which with fields:
 * telephone - String
 * price_id - Int
 * markup - Float
 */

class ControllerApiExchangeCustomers extends Controller {

    /**
     * Add types of prices
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
                $this->load->model('api/exchange/customers');
                $this->load->model('api/exchange/prices');

                $json['success'] = sprintf($this->language->get('error'));

                if (isset($this->request->post['update'])) {
                    $customers = $_POST['update'];

                    foreach (json_decode($customers) as $customer){
                        $telephone_from_server = $this->clearPhoneNumber($customer->telephone);
                        $start = strlen($telephone_from_server) - 9; //Last nine chars of the phone number
                        $phone_for_query = substr($telephone_from_server, $start);
                        $json['phone'] = $telephone_from_server;
                        $customer_id = $this->model_api_exchange_customers->getCustomerId($phone_for_query);
                        if( $customer_id ) {
                            $this->model_api_exchange_customers->
                            updateCustomer($customer_id, $customer->price_id, $customer->markup);
                        }else $json['message'] = 'Совпадений не найдено';
                    }
                    $json['success'] = sprintf($this->language->get('success'));

                } elseif (isset($this->request->post['get'])){
                    $telephones = array();
                    $result = $this->model_api_exchange_customers->getAllCustomers();
                    if ($result->num_rows > 0) {
                        foreach($result->rows as $row) {
                            $cleared_phone = $this->clearPhoneNumber($row['telephone']);
                            if (strcmp($cleared_phone, $row['telephone']) !== 0) {
                                $this->model_api_exchange_customers->
                                updateCustomerTelephone($row['customer_id'], $cleared_phone);
                            }

                            if (strlen($cleared_phone) === 10) $cleared_phone = '38'.$cleared_phone;
                            else if (strlen($cleared_phone) === 11) $cleared_phone = '3'.$cleared_phone;
                            array_push($telephones, $cleared_phone);
                        }
                    }
                    $json['customers'] = $telephones;
                    $json['success'] = sprintf($this->language->get('success'));
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function clearPhoneNumber($telephone){
        $telephone = str_replace('-', '', $telephone);
        $telephone = str_replace(' ', '', $telephone);
        $telephone = str_replace('+', '', $telephone);
        $telephone = str_replace('(', '', $telephone);
        $telephone = str_replace(')', '', $telephone);
        return $telephone;
    }

}

