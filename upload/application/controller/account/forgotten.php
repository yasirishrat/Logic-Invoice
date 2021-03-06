<?php
defined('_PATH') or die('Restricted!');

class ControllerAccountForgotten extends Controller {
    private $error = array();

    public function index() {
        $this->load->model('billing/customer');

        if ($this->customer->isLogged()) {
            $this->response->redirect($this->url->link('account/account', '', 'SSL'));
        }

        $this->data = $this->load->language('account/forgotten');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $password = substr(sha1(uniqid(mt_rand(), true)), 0, 10);

            $this->model_billing_customer->editPassword($this->request->post['email'], $password, $this->request->server['REMOTE_ADDR'], true);

            $this->session->data['success'] = $this->language->get('text_success');

            $customer_info = $this->model_billing_customer->getCustomerByEmail($this->request->post['email']);

            $this->load->model('system/activity');

            $this->model_system_activity->addActivity(sprintf($this->language->get('text_forgotten'), $customer_info['firstname'] . ' ' . $customer_info['lastname']));

            $this->response->redirect($this->url->link('account/login', '', 'SSL'));
        }

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', 'SSL')
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/forgotten', '', 'SSL')
        );

        $this->data['action'] = $this->url->link('account/forgotten', '', 'SSL');

        $this->data['error_warning'] = $this->build->data('warning', $this->error);
        $this->data['error_captcha'] = $this->build->data('captcha', $this->error);

        $this->data['email'] = $this->build->data('email', $this->request->post);
        $this->data['captcha'] = $this->build->data('captcha', $this->request->post);

        $this->data['captcha_image'] = $this->url->link('tool/captcha', '', 'SSL');

        $this->data['header'] = $this->load->controller('common/header');
        $this->data['footer'] = $this->load->controller('common/footer');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_theme') . '/template/account/forgotten.tpl')) {
            $this->response->setOutput($this->render($this->config->get('config_theme') . '/template/account/forgotten.tpl'));
        } else {
            $this->response->setOutput($this->render('default/template/account/forgotten.tpl'));
        }
    }

    protected function validate() {
        if (!$this->model_billing_customer->getCustomerByEmail($this->request->post['email'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        if ($this->request->post['captcha'] != $this->session->data['captcha']) {
            $this->error['captcha'] = $this->language->get('error_captcha');
        }

        return !$this->error;
    }
}