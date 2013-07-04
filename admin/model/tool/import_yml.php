<?php
class ModelToolImportYml extends Model {

	public function addCategory($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "category 
                            SET `category_id` = '" . (int)$data['category_id'] . "',
                                `parent_id`   = '" . (int)$data['parent_id'] . "',
                                `status` = 1, 
                                `date_modified` = NOW(), 
                                `date_added` = NOW()
                        ");

        $this->db->query("INSERT INTO " . DB_PREFIX . "category_description 
                            SET `category_id` = '" . (int)$data['category_id'] . "',
                                `language_id` = 1,
                                `name` = '". $this->db->escape(trim($data['name'])) ."'
                        ");

        $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store 
                            SET `category_id` = '" . (int)$data['category_id'] . "',
                                `store_id`    = '0'
                        ");

    }

    public function deleteCategories() {
        $this->db->query("TRUNCATE TABLE  `category`");
        $this->db->query("TRUNCATE TABLE  `category_description`");
        $this->db->query("TRUNCATE TABLE  `category_to_store`");
    }

    public function deleteProducts() {
        $this->db->query("TRUNCATE TABLE  `product`");
        $this->db->query("TRUNCATE TABLE  `product_attribute`");
        $this->db->query("TRUNCATE TABLE  `product_description`");
        $this->db->query("TRUNCATE TABLE  `product_discount`");
        $this->db->query("TRUNCATE TABLE  `product_image`");
        $this->db->query("TRUNCATE TABLE  `product_option`");
        $this->db->query("TRUNCATE TABLE  `product_option_value`");
        $this->db->query("TRUNCATE TABLE  `product_related`");
        $this->db->query("TRUNCATE TABLE  `product_reward`");
        $this->db->query("TRUNCATE TABLE  `product_special`");
        $this->db->query("TRUNCATE TABLE  `product_to_category`");
        $this->db->query("TRUNCATE TABLE  `product_to_download`");
        $this->db->query("TRUNCATE TABLE  `product_to_layout`");
        $this->db->query("TRUNCATE TABLE  `product_to_store`");
    }
}
?>
