<?php 
class ControllerToolImportYml extends Controller { 
  private $error = array();

  private $categoryMap = array();

  private $columnsUpdate = array();

  private $skuProducts = array();

  private $flushCount = 50;

  private $file;

  private $fileXML;

  private $settings = array();

  private $productsAdded = 0;

  private $productsUpdated = 0;
  
  public function index() 
  {
    $this->load->language('tool/import_yml');
    $this->document->setTitle($this->language->get('heading_title'));
    
    $this->load->model('tool/import_yml');
    $this->load->model('catalog/product');
    $this->load->model('catalog/manufacturer');
    $this->load->model('catalog/category');
    $this->load->model('catalog/attribute');
    $this->load->model('catalog/attribute_group');
    $this->load->model('localisation/language');
    $this->load->model('setting/setting');

    $this->settings['import_yml_file'] = $this->model_setting_setting->getSetting('import_yml_file');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
      $this->model_setting_setting->editSetting('import_yml', $this->request->post);

      $this->file = DIR_DOWNLOAD . 'import.yml';
      
      if ((isset( $this->request->files['upload'] )) && (is_uploaded_file($this->request->files['upload']['tmp_name']))) {
        move_uploaded_file($this->request->files['upload']['tmp_name'], $this->file);
      } elseif (!empty($this->request->post['url'])) {
        $file_content = file_get_contents($this->request->post['url']);
        file_put_contents($this->file, $file_content);
      }
      
      $force = (isset($this->request->post['force']) && $this->request->post['force'] == 'on');

      if (!empty($this->request->post['update'])) {
        $this->columnsUpdate = $this->request->post['update'];
      }

      $this->parseFile($this->file, $force);

      $this->model_setting_setting->editSetting('import_yml_file', array());

      $this->session->data['success'] = sprintf(
        $this->language->get('text_success'),
        $this->productsAdded,
        $this->productsUpdated
      );

      /*
      if (!empty($this->settings['import_yml_file'])) {
        $this->session->data['success'] = sprintf(
          $this->language->get('text_success_multiload'),
          $this->productsAdded,
          $this->productsUpdated,
          $this->settings['import_yml_file']['loaded'],
          $this->settings['import_yml_file']['offers'],
          $this->url->link('tool/import_yml/cancel', 'token=' . $this->session->data['token'], 'SSL')
        );

        $this->data['reload'] = true;
      }

      if ($this->settings['import_yml_file']['loaded'] > $this->settings['import_yml_file']['offers']) {
        $this->model_setting_setting->editSetting('import_yml_file', array());
      }*/
    }

    $this->data['heading_title'] = $this->language->get('heading_title');
    $this->data['entry_restore'] = $this->language->get('entry_restore');
    $this->data['entry_url'] = $this->language->get('entry_url');
    $this->data['entry_description'] = $this->language->get('entry_description');
    $this->data['entry_force'] = $this->language->get('entry_force');
    $this->data['entry_update'] = $this->language->get('entry_update');
    $this->data['entry_field_name'] = $this->language->get('entry_field_name');
    $this->data['entry_field_description'] = $this->language->get('entry_field_description');
    $this->data['entry_field_category'] = $this->language->get('entry_field_category');
    $this->data['entry_field_price'] = $this->language->get('entry_field_price');
    $this->data['entry_field_image'] = $this->language->get('entry_field_image');
    $this->data['entry_field_manufacturer'] = $this->language->get('entry_field_manufacturer');
    $this->data['entry_field_attribute'] = $this->language->get('entry_field_attribute');
    $this->data['entry_save_settings'] = $this->language->get('entry_save_settings');
    $this->data['button_import'] = $this->language->get('button_import');
    $this->data['button_save'] = $this->language->get('button_save');
    $this->data['tab_general'] = $this->language->get('tab_general');

    $this->data['settings'] = $settings = $this->model_setting_setting->getSetting('import_yml');
    
    $this->data['save'] = $this->url->link('tool/import_yml/save', 'token=' . $this->session->data['token'], 'SSL');

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

    $this->settings['import_yml_file'] = $this->model_setting_setting->getSetting('import_yml_file');

    if (!empty($this->settings['import_yml_file'])) {
      $this->data['loaded'] = $this->settings['import_yml_file']['loaded'];
      
      if ($this->data['loaded']) {
        $this->session->data['success'] = sprintf(
          $this->language->get('text_success_multiload'),
          $this->settings['import_yml_file']['loaded'],
          $this->url->link('tool/import_yml/cancel', 'token=' . $this->session->data['token'], 'SSL')
        );

        $this->data['reload'] = true;
      }
    }
        
    $this->template = 'tool/import_yml.tpl';
    $this->children = array(
      'common/header',
      'common/footer',
    );
    
    $this->response->setOutput($this->render());
  }

  private function parseFile($file, $force = false, $cli = false) 
  {
    set_time_limit(0);
    
    $xmlstr = file_get_contents($file);
    $xml = new SimpleXMLElement($xmlstr);

    $this->fileXML = $xml;

    if ($force) {
      $this->model_tool_import_yml->deleteCategories();
      $this->model_tool_import_yml->deleteProducts();
      $this->model_tool_import_yml->deleteManufactures();
      $this->model_tool_import_yml->deleteAttributes();
      $this->model_tool_import_yml->deleteAttributeGroups();
    }

    $result = $this->db->query('SELECT product_id, sku FROM `' . DB_PREFIX . 'product` ');
    if (!empty($result->rows)) {
      foreach ($result->rows as $row) {
        $this->skuProducts[ $row['sku'] ] = $row['product_id'];
      }
    }
    
    // Prepare big file upload feature
    if (!$cli) {
      if (empty($this->settings['import_yml_file']['file_hash'])
        || $this->settings['import_yml_file']['file_hash'] != md5($this->file)
      ) {
        $this->model_setting_setting->editSetting('import_yml_file', array(
          'file_hash' => md5($this->file),
          'file_name' => DIR_DOWNLOAD . 'import.yml',
          'loaded' => 0
        ));

        $this->settings['import_yml_file'] = $this->model_setting_setting->getSetting('import_yml_file');
      }
    }
    
    $this->addCategories($xml->shop->categories);
    $this->addProducts($xml->shop->offers, $force, $cli);
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
      $previousCount = count($categoriesList);
      
      foreach ($categoriesList as $source_category_id => $item) {
        if (array_key_exists((int)$item['parent_id'], $this->categoryMap)) {
          $category = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'category` INNER JOIN `' . DB_PREFIX . 'category_description` ON `' . DB_PREFIX . 'category_description`.category_id = `' . DB_PREFIX . 'category`.category_id WHERE parent_id = ' . (int)$this->categoryMap[$item['parent_id']] . ' AND `' . DB_PREFIX . 'category_description`.name LIKE "' . $this->db->escape($item['name']) . '"');
          
          if ($category->row) {
            $this->categoryMap[(int)$source_category_id] = $category->row['category_id'];
          } else {
            $category_data = array (
              'sort_order' => 0,
              'parent_id' => $this->categoryMap[ (int)$item['parent_id'] ],
              'top' => 0,
              'status' => 1,
              'column' => '',
              'category_description' => array (
                1 => array(
                  'name' => $item['name'],
                  'meta_title' => '',
                  'meta_keyword' => '',
                  'meta_description' => '',
                  'description' => '',
                  'seo_title' => $item['name'],
                  'seo_h1' => $item['name']
                )
              ),
              'keyword' => '',
              'category_store' => array (
                0
              ),
            );
            
            if ($category_data['parent_id'] == 0) {
              $category_data['top'] = 1;
            }
        
            $this->categoryMap[(int)$source_category_id] = $this->model_catalog_category->addCategory($category_data);
          }
          unset($categoriesList[$source_category_id]);
        }
      }
      
      if (count($categoriesList) === $previousCount) {
        break;
        //echo("Unliked tree path:\n");
        //print_r($categoriesList);
        //die();
      }
    }
  }

  private function addProducts($offers, $force = false, $cli = false) 
  {
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
    
    /*
        if ($force && is_dir(DIR_IMAGE . 'data/import_yml')) {
            $this->rrmdir(DIR_IMAGE . 'data/import_yml');
        }
    */
    
    if (!is_dir(DIR_IMAGE . 'data/import_yml')) {
        mkdir(DIR_IMAGE . 'data/import_yml');
    }

    $vendorMap = $this->model_tool_import_yml->loadManufactures();
    
    $attrMap = $this->model_tool_import_yml->loadAttributes();
    
    if (!$cli && !empty($this->settings['import_yml_file'])) {
      $start = (int)$this->settings['import_yml_file']['loaded'];
    } else {
      $start = 0;
    }

    //Here is start adding products
    $n = count($offers->offer);
    $flushCounter = $this->flushCount;
    
    //foreach ($offers->offer as $offer) {
    for ($i = $start; $i < $n; $i++) {
      $offer = $offers->offer[ $i ];

      $product_images = array();
      
      $dir_name = 'data/import_yml/' . implode('/', str_split((string)$offer['id'], 3)) . '/';
      if (!is_dir(DIR_IMAGE . $dir_name)) {
        mkdir(DIR_IMAGE . $dir_name, 0777, true);
      }
      
      foreach ($offer->picture as $picture) {        
        $img_name = substr(strrchr($picture, '/'), 1);
  
        if (!empty($img_name)) {
          $image = $this->loadImageFromHost($picture, DIR_IMAGE . $dir_name . $img_name);
          if ($image) {
            $product_images[] = array('image' => $dir_name . $img_name, 'sort_order' => count($product_images));
          }
        }
      }

      $image_path = array_shift($product_images);
      if (is_array($image_path)) {
        $image_path = $image_path['image'];
      }

      $productName = (string)$offer->name;
      if (!$productName) {
        if (isset($offer->typePrefix)) {
          $productName = (string)$offer->typePrefix . ' ' . (string)$offer->model;
        } else {
          $productName = (string)$offer->model;
        }
      }
      
      $languages = $this->model_localisation_language->getLanguages();

      foreach ($languages as $language) {
        $product_description[ $language['language_id'] ] = array (
          'name' => $productName,
          'meta_title' => '',
          'meta_keyword' => '',
          'meta_description' => '',
          'description' => (string)$offer->description,
          'tag' => '',
          'seo_title' => $productName,
          'seo_h1' => $productName,
        );
      }

      if (!isset($this->categoryMap[(int)$offer->categoryId])) {
        // continue;
        die('Invalid category id = ' . (int)$offer->categoryId . ' for offer ' . $offer['id']);
      }
      
      $data = array(
        'product_description' => $product_description,
        'product_special' => array (),
        'product_store' => array(0),
        'main_category_id' => $this->categoryMap[(int)$offer->categoryId],
        'product_category' => array (
          $this->categoryMap[(int)$offer->categoryId],
        ),
        'product_attribute' => array(),
        'model' => (string)$offer->vendorCode,
        'image' => $image_path,
        'sku'   => (isset($offer->vendorCode))? (string)$offer->vendorCode : (string)$offer['id'],
        'keyword' => (string)$offer->vendorCode,
        'upc'  => '',
        'ean'  => '',
        'jan'  => '',
        'isbn' => '',
        'mpn'  => '',
        'location' => '',
        'quantity' => '999',
        'minimum' => '',
        'subtract' => '',
        'stock_status_id' => ($offer['available'] == 'true')? 7:8,
        'date_available' => date('Y-m-d'),
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
        'product_image' => $product_images,
        'product_tag' => array(), // for ocstore 1.5.1
       );

       if (isset($offer->vendor)) {
        $vendor_name = (string)$offer->vendor;
        
        if (!isset($vendorMap[$vendor_name])) {
          $manufacturer_data = array (
            'name' => $vendor_name,
            'sort_order' => 0,
            'manufacturer_description' => array (
              1 => array (
                'meta_keyword' => '',
                'meta_description' => '',
                'description' => $vendor_name,
                'seo_title' => '',
                'seo_h1' => '',
              )
            ),
            'manufacturer_store' => array ( 0 ),
            'keyword' => '',
          );
          
          $vendorMap[$vendor_name] = $this->model_catalog_manufacturer->addManufacturer($manufacturer_data);
        }
        
        $data['manufacturer_id'] = $vendorMap[(string)$offer->vendor];
      }
      
      if (isset($offer->param)) {
        $params = $offer->param;
        
        foreach ($params as $param) {
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
            
            $attrMap[$attr_name] = $this->model_catalog_attribute->addAttribute($attr_data);
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

      if (array_key_exists($data['sku'], $this->skuProducts)) {
        $data = $this->changeDataByColumns($this->skuProducts[ $data['sku'] ], $data);
        $this->model_catalog_product->editProduct($this->skuProducts[ $data['sku'] ], $data);
        $this->productsUpdated++;
      } else {
        $this->skuProducts[ $data['sku'] ] = $this->model_catalog_product->addProduct($data);
        $this->productsAdded++;
      }

      --$flushCounter;
      
      if ($flushCounter <= 0) {
        $loaded = $i;

        $this->model_setting_setting->editSetting('import_yml_file', array(
          'file_hash' => md5($this->file),
          'offers' => count($offers->offer),
          'loaded' => $loaded
        ));

        $flushCounter = $this->flushCount;
      }
    }
  }

  private function loadImageFromHost($link, $img_path)
  {
    if (!file_exists($img_path)) {
      $ch = curl_init($link);
      $fp = fopen($img_path, "wb");
      if ($fp) {
        $options = array(CURLOPT_FILE => $fp,
                 CURLOPT_HEADER => 0,
                 /*CURLOPT_FOLLOWLOCATION => 1,*/
                 CURLOPT_TIMEOUT => 60,
              );

        curl_setopt_array($ch, $options);

        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
      }

      return file_exists($img_path);
    }
    
    return true;
  }

  private function rrmdir($dir)
  {
    foreach(glob($dir . '/*') as $file) {
      if(is_dir($file))
          rrmdir($file);
      else
          unlink($file);
    }
    rmdir($dir);
  }

  private function validate()
  {
    if (!$this->user->hasPermission('modify', 'tool/import_yml')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }
    
    if (!$this->error) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  private function changeDataByColumns($product_id, $data)
  {
    $productData = $this->model_catalog_product->getProduct($product_id);
    $productAttributes = $this->model_catalog_product->getProductAttributes($product_id);

    if (empty($this->columnsUpdate['name'])) {
      $data['product_description'][1]['name'] = $productData['name'];
    }

    if (empty($this->columnsUpdate['description'])) {
      $data['product_description'][1]['description'] = $productData['description'];
    }

    if (empty($this->columnsUpdate['category'])) {
      $productData = array_merge($productData, array('product_category' => $this->model_catalog_product->getProductCategories($product_id)));
      $data['product_category'] = $productData['product_category'];
    }
    
    if (empty($this->columnsUpdate['price'])) {
      $data['price'] = $productData['price'];
    }

    if (empty($this->columnsUpdate['image'])) {
      $data['image'] = $productData['image'];
    }

    if (empty($this->columnsUpdate['manufacturer'])) {
      $data['manufacturer_id'] = $productData['manufacturer_id'];
    }

    if (empty($this->columnsUpdate['attributes'])) {
      $data['product_attribute'] = $productAttributes;
    }

    return $data;
  }

  public function cron()
  {
    $this->load->language('tool/import_yml');
    $this->load->model('tool/import_yml');
    $this->load->model('catalog/product');
    $this->load->model('catalog/manufacturer');
    $this->load->model('catalog/category');
    $this->load->model('catalog/attribute');
    $this->load->model('catalog/attribute_group');
    $this->load->model('localisation/language');
    $this->load->model('setting/setting');

    $settings = $this->model_setting_setting->getSetting('import_yml');

    if (!empty($settings['url'])) {
      $force = (isset($settings['force']) && $settings['force'] == 'on');

      if (!empty($settings['update'])) {
        $this->columnsUpdate = $settings['update'];
      }

      $this->parseFile($settings['url'], $force, true);
    }
  }

  public function save()
  {
    $this->load->model('setting/setting');
    $this->load->language('tool/import_yml');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
      $this->model_setting_setting->editSetting('import_yml', $this->request->post);

      $this->session->data['success'] = $this->language->get('text_settings_success');
    }

    $this->redirect($this->url->link('tool/import_yml', 'token=' . $this->session->data['token'], 'SSL'));
  }

  public function resume()
  {
    $this->load->model('tool/import_yml');
    $this->load->model('setting/setting');
  }
  
  public function cancel()
  {
    $this->load->model('tool/import_yml');
    $this->load->model('setting/setting');

    unlink(DIR_DOWNLOAD . 'import.yml');
    
    $this->model_setting_setting->editSetting('import_yml_file', array());

    $this->redirect($this->url->link('tool/import_yml', 'token=' . $this->session->data['token'], 'SSL'));
  }
}
?>
