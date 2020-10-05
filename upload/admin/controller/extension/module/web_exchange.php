<?php

class ControllerExtensionModuleWebExchange extends Controller {
	private $error = array();

    /**
     * Installing additional tables = !!! That's function working only from Admin Controller !!! =
     */
    public function install() {
        $this->load->model('extension/module/web_exchange');
        $this->model_extension_module_web_exchange->installImagesTable();
        $this->model_extension_module_web_exchange->installPriceTables();
    }

    /**
     * Removing additional tables = !!! That's function working only from Admin Controller !!! =
     */
    public function uninstall() {
        $this->load->model('extension/module/web_exchange');
        $this->model_extension_module_web_exchange->uninstallImagesTable();
        $this->model_extension_module_web_exchange->uninstallPriceTables();
    }

	public function index() {

		$this->load->language('extension/module/web_exchange');
		$this->load->model('setting/setting');

		$this->document->setTitle(strip_tags($this->language->get('heading_title')));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_web_exchange', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/web_exchange', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/web_exchange', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_web_exchange_status'])) {
			$data['module_web_exchange_status'] = $this->request->post['module_web_exchange_status'];
		} else {
			$data['module_web_exchange_status'] = $this->config->get('module_web_exchange_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$data['user_token'] = $this->session->data['user_token'];

		$data['images'] = $this->getImagesLog();
		$data['exchange_timestamp'] = $this->getExchangeTimestamp();

		$this->response->setOutput($this->load->view('extension/module/web_exchange', $data));
	}

    /**
     * Getting images filenames from log
     * @return array
     */
	private function getImagesLog(){
        $log_images = __DIR__.'/log_images.hlp';
        $images = array();
        if(file_exists($log_images)){
            if ($fh = fopen($log_images, 'r')) {
                while (!feof($fh)) {
                    $line = fgets($fh);
                    if(strlen($line) > 3) array_push($images, $line);
                }
                fclose($fh);
            }
        }
        return $images;
    }

    /**
     * @return false|string
     */
    private function getExchangeTimestamp() {
        $log_exchange = __DIR__.'/exchange_timestamp.hlp';
        $timestamp = '';
        if(file_exists($log_exchange)){
            if ($fh = fopen($log_exchange, 'r')) {
                while (!feof($fh)) {
                    $timestamp .= fgets($fh);
                }
                fclose($fh);
            }
        }
        return $timestamp;
    }

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/web_exchange')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
