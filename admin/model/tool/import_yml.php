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

    public function addProduct($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product
                            SET `product_id` = '" . (int)$data['product_id'] . "',
                                `model` = '". $this->db->escape(trim($data['model'])) ."',
                                `price` = '" . (float)$data['price'] . "',
                                `status` = ". (int)$data['status'] .",
                                `stock_status_id` = ". (int)$data['stock_status_id'] .",
                                `image` = '". $this->db->escape(trim($data['image'])) ."',
                                `date_modified` = NOW(),
                                `date_added` = NOW()
                        ");

        $this->db->query("INSERT INTO " . DB_PREFIX . "product_description
                            SET `product_id` = '" . (int)$data['product_id'] . "',
                                `language_id` = '1',
                                `name` = '". $this->db->escape(trim($data['name'])) ."',
                                `description` = '". $this->db->escape($data['description']) ."'
                        ");

        $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category 
                            SET `product_id`  = '" . (int)$data['product_id'] . "',
                                `category_id` = '" . (int)$data['category_id'] . "'
                        ");

        $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store 
                            SET `product_id` = '" . (int)$data['product_id'] . "',
                                `store_id`   = '0'
                        ");
    }

    public function deleteCategories() {
        $this->db->query("TRUNCATE TABLE  `category`");
        $this->db->query("TRUNCATE TABLE  `category_description`");
        $this->db->query("TRUNCATE TABLE  `category_to_store`");
    }

    public function deleteProducts() {
        $this->db->query("TRUNCATE TABLE  `product`");
        $this->db->query("TRUNCATE TABLE  `product_description`");
        $this->db->query("TRUNCATE TABLE  `product_to_category`");
        $this->db->query("TRUNCATE TABLE  `product_to_store`");
    }
}
?>
