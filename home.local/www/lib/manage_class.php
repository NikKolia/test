<?php
require_once "config_class.php";
require_once "format_class.php";
require_once "product_class.php";
require_once "image_class.php";
require_once "post_class.php";
require_once "info_class.php";
require_once "order_class.php";
require_once "comment_class.php";
require_once "docadd_class.php";
require_once "video_class.php";
require_once "discount_class.php";
require_once "systemmessage_class.php";
require_once "mail_class.php";
require_once "url_class.php";

class Manage {
	
	protected $config;
	protected $format;
	protected $product;
	protected $image;
	protected $post;
	protected $info;
	protected $order;
	protected $comment;
	protected $docadd;
	protected $video;
	protected $discount;
	protected $url;
	
	public function __construct() {
		session_start();
		$this->config = new Config();
		$this->format = new Format();
		$this->product = new Product();
		$this->image = new Image();
		$this->post = new Post();
		$this->info = new Info();
		$this->order = new Order();
		$this->comment = new Comment();
		$this->docadd = new Docadd();
		$this->video = new Video();
		$this->discount = new Discount();
		$this->sm = new SystemMessage();
		$this->mail = new Mail();
		$this->url = new URL();
		$this->data = $this->format->xss($_REQUEST);
		$this->saveData();
	}
	
	private function saveData() {
		foreach ($this->data as $key => $value) $_SESSION[$key] = $value;
	}
	
	public function editCart($id = false) {
		if (!$id) $id = $this->data["id"];
		if (!$this->product->existsID($id)) return false;
		if ($_SESSION["cart"]) $_SESSION["cart"] .= ",$id";
		else $_SESSION["cart"] = $id; 
	}
	
	public function addCart($id = false) {
		if (!$id) $id = $this->data["id"];
		if (!$this->product->existsID($id)) return false;
		if ($_SESSION["cart"]) $_SESSION["cart"] .= ",$id";
		else $_SESSION["cart"] = $id;
		header("Location: ".$this->url->addCartoff($id));
		exit;
		/* $this->sm->message("SUCCESS_ADD_COMMENT");
		header("Location: ".$this->url->product($id));
		exit; */
	}
	
	public function deleteCart() {
		$id = $this->data["id"];
		$ids = explode(",", $_SESSION["cart"]);
		$_SESSION["cart"] = "";
		for ($i = 0; $i < count($ids); $i++) {
			if ($ids[$i] != $id) $this->editCart($ids[$i]);
		}
		header("Location: ".$this->url->Cart());
		exit;
	}
	
	public function updateCart() {
		$_SESSION["cart"] = "";
		foreach ($this->data as $k => $v) {
			if (strpos($k, "count_") !== false) {
				$id = substr($k, strlen("count_"));
				for ($i = 0; $i < $v; $i++) {
					if (!$id) $id = $this->data["id"];
					if (!$this->product->existsID($id)) return false;
					if ($_SESSION["cart"]) $_SESSION["cart"] .= ",$id";
					else $_SESSION["cart"] = $id;
				}
			}
		}
		// $_SESSION["discount"] = $this->data["discount"];
		header("Location: ".$this->url->Cart());
		exit;
	}
	
	public function addOrder() {
		$temp_data = array();
		$temp_data["delivery"] = $this->data["delivery"];
		$temp_data["product_ids"] = $_SESSION["cart"];
		$temp_data["price"] = $this->getPrice();
		$temp_data["name"] = $this->data["name"];
		$temp_data["phone"] = $this->data["phone"];
		$temp_data["email"] = $this->data["email"];
		$temp_data["address"] = $this->data["address"];
		$temp_data["notice"] = $this->data["notice"];
		$temp_data["date_order"] = $this->format->ts();
		$temp_data["date_send"] = 0;
		$temp_data["date_pay"] = 0;
		$id = $this->order->add($temp_data);
		if ($id) {
			$send_data = array();
			$send_data["id"] = $id;
			$send_data["products"] = $this->getProducts();
			$send_data["name"] = $temp_data["name"];
			$send_data["phone"] = $temp_data["phone"];
			$send_data["email"] = $temp_data["email"];
			$send_data["address"] = $temp_data["address"];
			$send_data["notice"] = $temp_data["notice"];
			$send_data["price"] = $temp_data["price"];
			$to_adm = $this->config->admemail;
			$to_user = $temp_data["email"];
			$this->mail->send_adm($this->config->admemail, $send_data, "ORDER");
			$this->mail->send_user($temp_data["email"], $send_data, "ORDER");
			header("Location: ".$this->url->addOrder($id));
			exit;
		}
		header("Location: ".$this->url->order());
		exit;
	}

	public function addComments() {
		$temp_data = array();
		$temp_data["product_id"] = $this->data["product_id"];
		$temp_data["name"] = $this->data["name"];
		// $temp_data["email"] = $this->data["email"];
		$temp_data["comment"] = $this->data["comment"];
		$temp_data["datecom"] = $this->format->ts();
		$id = $this->comment->add($temp_data);
		if ($id) {
			/* $send_data = array();
			$send_data["id"] = $id;
			$send_data["product_id"] = $this->getProduct();
			$send_data["name"] = $temp_data["name"];
			$send_data["email"] = $temp_data["email"];
			$send_data["comment"] = $temp_data["comment"];
			$to_adm = $this->config->admemail;
			$to_user = $temp_data["email"];
			$this->mail->send_adm($this->config->admemail, $send_data, "COMMENT");
			$this->mail->send_user($temp_data["email"], $send_data, "COMMENT"); */
			$this->sm->message("SUCCESS_ADD_COMMENT");
			header("Location: ".$this->url->product($this->data["product_id"]));
			exit;
		}
		header("Location: ".$this->url->product($this->data["product_id"]));
		exit;
	}
	
	public function docadd() {
		$temp_data = array();
		$temp_data["section_id"] = $this->data["section_id"];
		$temp_data["price"] = $this->data["price"];
		$temp_data["frame"] = $this->data["frame"];
		$temp_data["model"] = $this->data["model"];
		$temp_data["title"] = $this->data["pr_title"];
		$temp_data["description"] = $this->data["description"];
		$temp_data["datedg"] = $this->data["datedg"];
		/* $img = $this->loadDoc();
		// if (!$img) return false;
		$temp_data["img"] = $img; */
		$id = $this->docadd->add($temp_data);
		if ($id) {
			$this->sm->message("SUCCESS_ADD_COMMENT");
			header("Location: ".$this->url->index());
			exit;
		}
		header("Location: ".$this->url->docadds());
		exit;
	}
	
	/* private function loadDoc() {
		$img = $_FILES["img"];
		if (!$img["name"]) return $this->sm->message("ERROR_IMG");
		if (!$this->isSecure($img)) return false;
		$uploadfile = $this->config->dir_img_products.$img["name"];
		if (file_exists($uploadfile)) return $this->sm->message("ERROR_EXISTS_IMG");
		if (move_uploaded_file($img["tmp_name"], $uploadfile)) return $img["name"];
		else return $this->sm->unknownError();
	} */
	
	private function isSecure($img) {
		$blacklist = array(".php", ".phtml", ".php3", ".php4", ".html", ".htm");
		foreach ($blacklist as $item)
			if (preg_match("/$item\$/i", $img["name"])) return false;
		$type = $img["type"];
		$size = $img["size"];
		// if (($type != "image/jpg") && ($type != "image/jpeg") && ($type != "image/png")) return $this->sm->message("ERROR_TYPE_IMG");
		if ($size > $this->config->max_size_img) return $this->sm->message("ERROR_SIZE_IMG");
		return true;
	}
	
	public function successPay() {
		return $this->sm->pageMessage("SUCCESS_PAY", true);
	}
	
	public function failPay() {
		return $this->sm->pageMessage("FAIL_PAY", true);
	}
	
	public function statusPay() {
		if ($this->data["ik_payment_state"] == "success") {
			$secret_key = "y2PlvVXsD7W13tWA";
			$sign = $this->data['ik_shop_id'].':'.
					$this->data['ik_payment_amount'].':'.
					$this->data['ik_payment_id'].':'.
					$this->data['ik_paysystem_alias'].':'.
					$this->data['ik_baggage_fields'].':'.
					$this->data['ik_payment_state'].':'.
					$this->data['ik_trans_id'].':'.
					$this->data['ik_currency_exch'].':'.
					$this->data['ik_fees_payer'].':'.
					$secret_key;
			$sign = mb_strtoupper(md5($sign));
			if($this->data['ik_sign_hash'] === $sign) {
				if ($this->order->getPrice($this->data['ik_payment_id']) == $this->data["ik_payment_amount"])
					$this->order->setDatePay($this->data['ik_payment_id'], $this->format->ts());
			}
		}
	}
	
	private function getProduct() {
		$ids = explode(",", $this->data["product_id"]);
		$products = $this->product->getAllOnIDs($ids);
		$result = array();
		for ($i = 0; $i < count($products); $i++) {
			$result[$products[$i]["id"]] = $products[$i]["title"];
		}
		$products = array();
		for ($i = 0; $i < count($ids); $i++) {
			$products[$ids[$i]][0]++;
			$products[$ids[$i]][1] = $result[$ids[$i]];
		}
		$str = "";
		foreach ($products as $value) {
			$str .= $value[1]." | ";
		}
		$str = substr($str, 0, -3);
		return $str;
	}
	
	private function getProducts() {
		$ids = explode(",", $_SESSION["cart"]);
		$products = $this->product->getAllOnIDs($ids);
		$result = array();
		for ($i = 0; $i < count($products); $i++) {
			$result[$products[$i]["id"]] = $products[$i]["title"];
		}
		$products = array();
		for ($i = 0; $i < count($ids); $i++) {
			$products[$ids[$i]][0]++;
			$products[$ids[$i]][1] = $result[$ids[$i]];
			$products[$ids[$i]][2] = $ids[$i];
		}
		$str = "";
		foreach ($products as $value) {
			$str .= $value[1]." (код товара - ".$value[2].") : ".$value[0]." шт. || ";
		}
		$str = substr($str, 0, -3);
		return $str;
	}
	
	private function getPrice() {
		$ids = explode(",", $_SESSION["cart"]);
		$summa = $this->product->getPriceOnIDs($ids);
		$value = $this->discount->getValueOnCode($_SESSION["discount"]);
		if ($value) $summa *= (1 - $value);
		return $summa;
	}
}
?>