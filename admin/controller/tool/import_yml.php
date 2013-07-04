<?php 
class ControllerToolImportYml extends Controller { 
	private $error = array();
	
	public function index() 
    {
        $this->load->language('tool/import_yml');
		$this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('tool/import_yml');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {

			if ((isset( $this->request->files['upload'] )) && (is_uploaded_file($this->request->files['upload']['tmp_name']))) {
				$file = DIR_DOWNLOAD . 'import.yml';
				move_uploaded_file($this->request->files['upload']['tmp_name'], $file);
				
                $this->parseFile($file);

                $this->session->data['success'] = $this->language->get('text_success');
			}
		}

		$this->data['heading_title'] = $this->language->get('heading_title');
		$this->data['entry_restore'] = $this->language->get('entry_restore');
		$this->data['entry_description'] = $this->language->get('entry_description');
		$this->data['button_import'] = $this->language->get('button_import');
		$this->data['tab_general'] = $this->language->get('tab_general');

 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		
		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
		
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => FALSE
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('tool/export', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);
		
		$this->data['action'] = $this->url->link('tool/import_yml', 'token=' . $this->session->data['token'], 'SSL');

		$this->data['export'] = $this->url->link('tool/import_yml/download', 'token=' . $this->session->data['token'], 'SSL');

		$this->template = 'tool/import_yml.tpl';
		$this->children = array(
			'common/header',
			'common/footer',
		);
		$this->response->setOutput($this->render());
	}

    private function parseFile($file) 
    {
        $xmlstr = file_get_contents($file);
        $xml = new SimpleXMLElement($xmlstr);

        $this->addCategories($xml->shop->categories);
        $this->addProducts($xml->shop->offers);

    }

    private function addCategories($categories) 
    {
        $this->model_tool_import_yml->deleteCategories();

        foreach ($categories->category as $category) {
            $data = array(
                'category_id' => $category['id'],
                'parent_id'   => $category['parentId'],
                'name'        => $category
            );

            $this->model_tool_import_yml->addCategory($data);
        }
    }

    private function addProducts($offers) 
    {
        $this->model_tool_import_yml->deleteProducts();

        if (is_dir(DIR_IMAGE . 'data/import_yml')) {
            $this->rrmdir(DIR_IMAGE . 'data/import_yml');
        }

        if (!is_dir(DIR_IMAGE . 'data/import_yml')) {
            mkdir(DIR_IMAGE . 'data/import_yml');
        }

        foreach ($offers->offer as $offer) {
            $image = null;
            if (is_dir(DIR_IMAGE . 'data/import_yml')) {
                $img_name = substr(strrchr($offer->picture, '/'), 1);
    
                if (!empty($img_name)) {
                    $image = $this->loadImageFromHost($offer->picture, DIR_IMAGE . 'data/import_yml/' . $img_name);
                    if ($image) {
                        $image_path = 'data/import_yml/' . $img_name;
                    }
                }
            }

            $data = array(
                'product_id'  => $offer['id'],
                'category_id' => $offer->categoryId,
                'model'      => $offer->vendorCode,
                'price'      => $offer->price,
                'image'      => $image_path,
                'name'       => $offer->name,
                'description' => $offer->description,
                'stock_status_id' => 7,
                'status' => ($offer['available'] == 'true')? 1:0
            );
            
            $this->model_tool_import_yml->addProduct($data);
        }
    }

    private function loadImageFromHost($link, $img_path)
    {
        $ch = curl_init($link);
        $fp = fopen($img_path, "wb");
        if ($fp) {
            $options = array(CURLOPT_FILE => $fp,
                             CURLOPT_HEADER => 0,
                             //CURLOPT_FOLLOWLOCATION => 1,
                             CURLOPT_TIMEOUT => 60);

            curl_setopt_array($ch, $options);

            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }

        return file_exists($img_path);
    }

    private function rrmdir($dir) {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file))
                rrmdir($file);
            else
                unlink($file);
        }
        rmdir($dir);
    }

    private function validate() {
		if (!$this->user->hasPermission('modify', 'tool/export')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}
?>
