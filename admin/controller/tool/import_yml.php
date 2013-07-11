<?php 
class ControllerToolImportYml extends Controller { 
	private $error = array();
	
	private $categoryMap = array();
	
	public function index() 
    {
        $this->load->language('tool/import_yml');
		$this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('tool/import_yml');
        $this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/attribute');
		$this->load->model('catalog/attribute_group');

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
		$this->categoryMap = array(
			0 => 0
		);
		
		$categoriesList = array();
		
        foreach ($categories->category as $category) {
            $categoriesList[ (string)$category['id'] ] = array(
                'parent_id'   => (int)$category['parentId'],
                'name'        => trim((string)$category)
            );
		}
		
		// Compare categories level by level and create new one, if it doesn't exist
		while (count($categoriesList) > 0) {
			foreach ($categoriesList as $source_category_id => $item) {
				if (array_key_exists((int)$item['parent_id'], $this->categoryMap)) {
					$category = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'category` INNER JOIN `' . DB_PREFIX . 'category_description` ON `' . DB_PREFIX . 'category_description`.category_id = `' . DB_PREFIX . 'category`.category_id WHERE parent_id = ' . (int)$this->categoryMap[$item['parent_id']] . ' AND `' . DB_PREFIX . 'category_description`.name LIKE "' . $this->db->escape($item['name']) . '"');
					
					if ($category->row) {
						$this->categoryMap[(int)$source_category_id] = $category->row['category_id'];
					} else {
						$category_data = array (
							'sort_order' => 0,
							'name' => $item['name'],
							'parent_id' => $this->categoryMap[ (int)$item['parent_id'] ],
							'top' => 0,
						);
						
						if ($category_data['parent_id'] == 0) {
							$category_data['top'] = 1;
						}
				
						$this->categoryMap[(int)$source_category_id] = $this->model_tool_import_yml->addCategory($category_data);
					}
					unset($categoriesList[$source_category_id]);
				}
			}
        }
    }

    private function addProducts($offers) 
    {
        //$this->model_tool_import_yml->deleteProducts();

		// get first attribute group
		$res = $this->db->query('SELECT * FROM `' . DB_PREFIX  . 'attribute_group` ORDER BY `attribute_group_id` LIMIT 0, 1');
		if (!$res->row) {
			$attr_group_data = array (
				'sort_order' => 0,
				'attribute_group_description' => array (
					1 => array (
						'name' => 'Basic',
					),
				)
			);
			$attrGroupId = $this->model_catalog_attribute_group->addAttributeGroup($attr_group_data);
		} else {
			$attrGroupId = (int)$res->row['attribute_group_id'];
		}
		
        if (is_dir(DIR_IMAGE . 'data/import_yml')) {
            $this->rrmdir(DIR_IMAGE . 'data/import_yml');
        }

        if (!is_dir(DIR_IMAGE . 'data/import_yml')) {
            mkdir(DIR_IMAGE . 'data/import_yml');
        }

		$vendorMap = $this->model_tool_import_yml->loadManufactures();
		
		$attrMap = $this->model_tool_import_yml->loadAttributes();
		
        foreach ($offers->offer as $offer) {
            $image_path = null;
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
                'product_description' => array ( 
                    1 => array (
                        'name' => $offer->name,
                        'meta_keyword' => '',
                        'meta_description' => '',
                        'description' => $offer->description,
                        'tag' => '',
                        'seo_title' => '',
                        'seo_h1' => '',
                    )
                ),
                'product_special' => array (),
                'product_store' => array(0),
                'main_category_id' => $this->categoryMap[(int)$offer->categoryId],
                'product_category' => array (
                    $this->categoryMap[(int)$offer->categoryId],
                ),
				'product_attribute' => array(),
                'model' => $offer->vendorCode,
                'image' => $image_path,
                'sku'   => $offer->vendorCode,
                'keyword' => $offer->vendorCode,
                'upc'  => '',
                'ean'  => '',
                'jan'  => '',
                'isbn' => '',
                'mpn'  => '',
                'location' => '',
                'quantity' => '',
                'minimum' => '',
                'subtract' => '',
                'stock_status_id' => ($offer['available'] == 'true')? 7:8,
                'date_available' => '',
                'manufacturer_id' => '',
                'shipping' => 1,
                'price' => (float)$offer->price,
                'points' => '',
                'weight' => '', 
                'weight_class_id' => '',
                'length' => '',
                'width' => '',
                'height' => '',
                'length_class_id' => '',
                'status' => '1',
                'tax_class_id' => '',
                'sort_order' => '',
           );

		   if (isset($offer->vendor)) {
				$vendor_name = (string)$offer->vendor;
				
				if (!isset($vendorMap[$vendor_name])) {
					$manufacturer_data = array (
						'name' => $vendor_name,
						'sort_order' => 0,
						'manufacturer_description' => array (
						),
						'manufacturer_store' => array ( 0 ),
					);
					
					$vendorMap[$vendor_name] = $this->model_tool_import_yml->addManufacturer($manufacturer_data);
				}
				
				$data['manufacturer_id'] = $vendorMap[(string)$offer->vendor];
			}
			
			if (isset($offer->param)) {
				if (!is_array($offer->param)) {
					$offer->param = array($offer->param);
				}
				
				foreach ($offer->param as $param) {
					$attr_name = (string)$param['name'];
					$attr_value = (string)$param;
					
					if (array_key_exists($attr_name, $attrMap) === false) {
						$attr_data = array (
							'sort_order' => 0,
							'attribute_group_id' => $attrGroupId,
							'attribute_description' => array (
								1 => array (
									'name' => $attr_name,
								)
							),
						);
						
						$attrMap[$attr_name] = $this->model_tool_import_yml->addAttribute($attr_data);
					}
					
					$data['product_attribute'][] = array (
						'attribute_id' => $attrMap[$attr_name],
						'product_attribute_description' => array (
							1 => array (
								'text' => $attr_value,
							)
						)
					);
				}
			}
			
			$result = $this->db->query('SELECT product_id FROM `' . DB_PREFIX . 'product` WHERE `sku` LIKE "' . $data['sku'] . '"');
			if ($result->row) {
				$this->model_catalog_product->editProduct($result->row['product_id'], $data); 
			} else {
				$this->model_catalog_product->addProduct($data); 
			}
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
                             CURLOPT_TIMEOUT => 60,
                        );

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
