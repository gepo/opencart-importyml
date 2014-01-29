<?php
class ModelToolImportYml extends Model {
  public function loadManufactures() {
    $manufactures = $this->db->query('SELECT * FROM `' . DB_PREFIX  . 'manufacturer`');
    
    $result = array();
    
    foreach ($manufactures->rows as $item) {
      $result[ $item['name'] ] = $item['manufacturer_id'];
    }
    
    return $result;
  }
  
  public function loadAttributes() {
    $attributes = $this->db->query('SELECT * FROM `' . DB_PREFIX  . 'attribute` INNER JOIN `' . DB_PREFIX  . 'attribute_description` ON `' . DB_PREFIX  . 'attribute_description`.attribute_id = `' . DB_PREFIX  . 'attribute`.attribute_id WHERE language_id = 1');
    
    $result = array();
    
    foreach ($attributes->rows as $item) {
      $result[ $item['name'] ] = $item['attribute_id'];
    }
    
    return $result;
  }
  
  public function deleteCategories() {
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "category`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "category_description`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "category_to_store`");
    
    if (version_compare(VERSION, '1.5.5', '>=')) {
      $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "category_path`");
    }
  }
  
  public function deleteProducts() {
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_attribute`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_description`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_discount`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_image`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_option`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_option_value`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_related`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_reward`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_special`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_to_category`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_to_download`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_to_layout`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "product_to_store`");
    
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "url_alias`");
  }
  
  public function deleteManufactures() {
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "manufacturer`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "manufacturer_description`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "manufacturer_to_store`");
  }
  
  public function deleteAttributes() {
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "attribute`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "attribute_description`");
  }
  
  public function deleteAttributeGroups() {
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "attribute_group`");
    $this->db->query("TRUNCATE TABLE  `" . DB_PREFIX  . "attribute_group_description`");
  }
}
?>
