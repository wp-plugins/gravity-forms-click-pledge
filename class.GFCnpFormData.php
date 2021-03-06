<?php

/**
* class for managing form data
*/
class GFCnpFormData {

	public $amount = 0;
	public $total = 0;
	public $ccName = '';
	public $ccNumber = '';
	public $ccExpMonth = '';
	public $ccExpYear = '';
	public $ccCVN = '';
	
	//e-Check
	public $ecRouting = '';
	public $ecCheck = '';
	public $ecAccount = '';	
	public $ecAccount_type = '';
	public $ecName = '';
	public $ecChecktype = '';
	public $ecIdtype = '';
	
	//Custom Payment
	public $paymentnumber;
	
	public $namePrefix = '';
	public $firstName = '';
	public $lastName = '';
	public $email = '';
	public $address = '';						// simple address, for regular payments
	public $address_street = '';				// street address, for recurring payments
	public $address_suburb = '';				// suburb, for recurring payments
	public $address_state = '';					// state, for recurring payments
	public $address_country = '';				// country, for recurring payments
	public $postcode = '';						// postcode, for both regular and recurring payments
	
	//Shipping address
	public $address_shipping = '';						// simple address, for regular payments
	public $address_street_shipping = '';				// street address, for recurring payments
	public $address_suburb_shipping = '';				// suburb, for recurring payments
	public $address_state_shipping = '';					// state, for recurring payments
	public $address_country_shipping = '';				// country, for recurring payments
	public $postcode_shipping = '';						// postcode, for both regular and recurring payments
	
	public $phone = '';							// phone number, for recurring payments
	public $recurring = FALSE;					// false, or an array of inputs from complex field
	public $ccField = FALSE;					// handle to meta-"field" for credit card in form
	public $ecField = FALSE;					// handle to meta-"field" for e-Check in form
	
	public $productdetails = array();
	public $customfields = array();
	public $needtovalidatefields = array();
	public $shippingfields = array();
	public $couponfields = array();
	
	//Duplicate fields checking
	public $creditcardCount = 0;
	public $echeckCount = 0;
	public $custompaymentCount = 0;
	public $shippingCount = 0;
	public $recurringCount = 0;
	public $namefieldCount = 0;
	
	private $isLastPageFlag = FALSE;
	private $isCcHiddenFlag = FALSE;
	private $isEcheckHiddenFlag = FALSE;
	private $hasPurchaseFieldsFlag = FALSE;

	/**
	* initialise instance
	* @param array $form
	*/
	public function __construct(&$form) {
		// check for last page
        $current_page = GFFormDisplay::get_source_page($form['id']);
        $target_page = GFFormDisplay::get_target_page($form, $current_page, rgpost('gform_field_values'));
        $this->isLastPageFlag = ($target_page == 0);

		// load the form data
		$this->loadForm($form);
	}
	
	
	/**
	* load the form data we care about from the form array
	* @param array $form
	*/
	private function loadForm(&$form) {
		foreach ($form['fields'] as &$field) {
			$id = $field['id'];
			$checkbox_array = $multiselect_array = $list_array = array();
			//echo RGFormsModel::get_input_type($field).'<br>';
			switch(RGFormsModel::get_input_type($field)){
				case 'name':
					$this->namefieldCount++;
					// only pick up the first name field (assume later ones are additional info)
					if (empty($this->firstName) && empty($this->lastName)) {
						$this->namePrefix = rgpost("input_{$id}_2");
						$this->firstName = rgpost("input_{$id}_3");
						$this->lastName = rgpost("input_{$id}_6");
					}
					else
					{
						$item_custom['FieldName'] = $field["label"];
						$anothername = rgpost("input_{$id}_2");
						if(rgpost("input_{$id}_3"))
						$anothername .= ' ' . rgpost("input_{$id}_3");
						if(rgpost("input_{$id}_6"))
						$anothername .= ' ' . rgpost("input_{$id}_6");
						$item_custom['FieldValue'] = $anothername;
						$item_custom['FieldId'] = $id;
						//if($item_custom['FieldValue'])
						//$this->customfields[] = $item_custom;
						if($item_custom['FieldValue'])
						{
							if(count($item_custom)) {
								$hasfield = false;
								if(count($this->customfields))
								{
									foreach($this->customfields as $cfield)
									{
										if($cfield['FieldId'] == $item_custom['FieldId'])
										$hasfield = true;
									}
								}
								if(!$hasfield)
								$this->customfields[] = $item_custom;
							}
						}
					}
					break;
					
				
				case 'email':
					// only pick up the first email address field (assume later ones are additional info)
					if (empty($this->email)) {
						$this->email = rgpost("input_{$id}");
					}
					else {
						$item_custom['FieldName'] = $field["label"];
						$item_custom['FieldValue'] = rgpost("input_{$id}");
						//if($item_custom['FieldValue'])
						//$this->customfields[] = $item_custom;
						if($item_custom['FieldValue'])
						{
							if(count($item_custom)) {
								$hasfield = false;
								if(count($this->customfields))
								{
									foreach($this->customfields as $cfield)
									{
										if($cfield['FieldId'] == $item_custom['FieldId'])
										$hasfield = true;
									}
								}
								if(!$hasfield)
								$this->customfields[] = $item_custom;
							}
						}
					}
					break;

				case 'phone':
					// only pick up the first phone number field (assume later ones are additional info)
					if (empty($this->phone)) {
						$this->phone = rgpost("input_{$id}");
					} else {
						$item_custom['FieldName'] = $field["label"];
						$item_custom['FieldValue'] = rgpost("input_{$id}");
						$item_custom['FieldId'] = $id;
						//if($item_custom['FieldValue'])
						//$this->customfields[] = $item_custom;
						if($item_custom['FieldValue'])
						{
							if(count($item_custom)) {
								$hasfield = false;
								if(count($this->customfields))
								{
									foreach($this->customfields as $cfield)
									{
										if($cfield['FieldId'] == $item_custom['FieldId'])
										$hasfield = true;
									}
								}
								if(!$hasfield)
								$this->customfields[] = $item_custom;
							}
						}
					}
					break;

				case 'address':
					// only pick up the first address field (assume later ones are additional info, e.g. shipping)
					if (empty($this->address) && empty($this->postcode)) 
					{
						$this->postcode = trim(rgpost("input_{$id}_5"));
						$parts = array(trim(rgpost("input_{$id}_1")), trim(rgpost("input_{$id}_2")));
						$this->address_street = implode(', ', array_filter($parts, 'strlen'));
						$this->address_suburb = trim(rgpost("input_{$id}_3"));
						$this->address_state = trim(rgpost("input_{$id}_4"));
						$this->address_country = trim(rgpost("input_{$id}_6"));

						// aggregate street, city, state, country into a single string (for regular one-off payments)
						$parts = array($this->address_street, $this->address_suburb, $this->address_state, $this->address_country);
						$this->address = implode(', ', array_filter($parts, 'strlen'));
					}
					elseif(empty($this->address_shipping) && empty($this->postcode_shipping)) 
					{
						$this->postcode_shipping = trim(rgpost("input_{$id}_5"));
						$parts = array(trim(rgpost("input_{$id}_1")), trim(rgpost("input_{$id}_2")));
						$this->address_street_shipping = implode(', ', array_filter($parts, 'strlen'));
						$this->address_suburb_shipping = trim(rgpost("input_{$id}_3"));
						$this->address_state_shipping = trim(rgpost("input_{$id}_4"));
						$this->address_country_shipping = trim(rgpost("input_{$id}_6"));

						// aggregate street, city, state, country into a single string (for regular one-off payments)
						$parts = array($this->address_street_shipping, $this->address_suburb_shipping, $this->address_state_shipping, $this->address_country_shipping);
						$this->address_shipping = implode(', ', array_filter($parts, 'strlen'));
					}
					else
					{
						$item_custom['FieldName'] = $field["label"];
						$parts = array(trim(rgpost("input_{$id}_1")), trim(rgpost("input_{$id}_2")));
						$str1 = implode(', ', array_filter($parts, 'strlen'));
						$parts = array($str, trim(rgpost("input_{$id}_3")), trim(rgpost("input_{$id}_4")), trim(rgpost("input_{$id}_6")));
						$str2 = implode(', ', array_filter($parts, 'strlen'));
						
						$item_custom['FieldValue'] = implode(', ', array_filter($str2, 'strlen'));
						$item_custom['FieldId'] = $id;
						if($item_custom['FieldValue'])
						{
							if(count($item_custom)) {
								$hasfield = false;
								if(count($this->customfields))
								{
									foreach($this->customfields as $cfield)
									{
										if($cfield['FieldId'] == $item_custom['FieldId'])
										$hasfield = true;
									}
								}
								if(!$hasfield)
								$this->customfields[] = $item_custom;
							}
						}
						//$this->customfields[] = $item_custom;
					}
					break;

				case 'creditcard':
					$this->isCcHiddenFlag = RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'));
					$this->ccField =& $field;
					$this->ccName = rgpost("input_{$id}_5");
					$this->ccNumber = self::cleanCcNumber(rgpost("input_{$id}_1"));
					$ccExp = rgpost("input_{$id}_2");
					if (is_array($ccExp))
						list($this->ccExpMonth, $this->ccExpYear) = $ccExp;
					$this->ccCVN = rgpost("input_{$id}_3");
					//if($this->ccNumber != '' && $this->ccName != '' && $this->ccExpMonth != '' && $this->ccExpYear != '' && $this->ccCVN != '') {
					$this->creditcardCount++;
					//}
					break;
					
				case 'gfcnpecheck':
					$this->isEcheckHiddenFlag = RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'));
					$this->ecField =& $field;
					$echeck = rgpost('gfp_' . $id);
					$this->ecRouting = $echeck['routing'];
					$this->ecCheck = $echeck['check'];
					$this->ecAccount = $echeck['account'];
					$this->ecAccount_type = $echeck['account_type'];
					$this->ecName = $echeck['name'];
					$this->ecChecktype = $echeck['checktype'];
					$this->ecIdtype = $echeck['idtype'];
					//if($this->ecRouting && $this->ecCheck && $this->ecAccount && $this->ecAccount_type && $this->ecName && $this->ecChecktype && $this->ecIdtype) {
					$this->echeckCount++;
					//}
					break;
				case 'gfcnpcustompayment':
					$this->isEcheckHiddenFlag = RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'));
					$this->custompaymentField =& $field;
					$custompayment = rgpost('gfp_' . $id);
					if(is_array($custompayment)) {
						$this->paymentnumber = $custompayment['paymentnumber'];
					} else {
						$this->paymentnumber = '';
					}
					$this->custompaymentCount++;
					break;
					
				case 'total':
					$this->total = GFCommon::to_number(rgpost("input_{$id}"));
					$this->hasPurchaseFieldsFlag = true;
					break;
					
				case 'coupon':					
					$coupondetails = json_decode(rgpost("gf_coupons_" . $field['formId']));
					foreach($coupondetails as $coupon => $details) {
						$item_coupon['amount'] = $details->amount;
						$item_coupon['type'] = $details->type;						
						$item_coupon['name'] = $details->name;
						$item_coupon['code'] = $details->code;
						$item_coupon['can_stack'] = $details->can_stack;
						$item_coupon['usage_count'] = $details->usage_count;						
						if ($details->type == 'percentage' && $details->amount != 100) {
							 $this->amount = $this->amount - ($this->amount * $item_coupon['amount'])/100;
						} else if ($details->type == 'percentage' && $details->amount == 100) {
							$this->amount = 0;
						}
						if ($details->type == 'flat') {
							$this->amount = $this->amount - $item_coupon['amount'];
						}
						$this->couponfields[] = $item_coupon;
					}	
					break;
					
				case GFCNP_FIELD_RECURRING:
					$this->recurringCount++;
					// only pick it up if it isn't hidden
					if (!RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'))) {
						$this->recurring = GFCnpRecurringField::getPost($id);
					}
					break;

				default:
					// check for product field
					if (GFCommon::is_product_field($field['type']) || $field['type'] == 'donation') {
						$this->amount += self::getProductPrice($form, $field);
						$this->hasPurchaseFieldsFlag = true;						
					}
					elseif(!GFCommon::is_post_field($field)) 
					//else
					{					
						switch($field['type'])
						{
							case 'checkbox':								
								$inputs = $field['inputs'];
								$checkbox_array = array();
								for($c = 1; $c <= count($inputs); $c++) {
									$val = rgpost("input_{$id}_{$c}");
									if($val) {
										$item_custom['FieldName'] = $field["label"] . ': ' . $val;
										$item_custom['FieldValue'] = 'Checked';
										$item_custom['FieldId'] = $id;
										$checkbox_array[] = $item_custom;
									} else {
										$item_custom['FieldName'] = $field["label"] . ': ' . $inputs[$c-1]['label'];
										$item_custom['FieldValue'] = 'Not Checked';
										$item_custom['FieldId'] = $id;
										$checkbox_array[] = $item_custom;
									}
								}								
																
							break;							
							case 'multiselect':
								$choices = $field['choices'];
								$inputs = rgpost("input_{$id}");														
								$multiselect_array = array();
								for($c = 0; $c < count($choices); $c++) {
									if(in_array($choices[$c]['value'], $inputs)) {
										$item_custom['FieldName'] = $field["label"] . ': ' . $choices[$c]['value'];
										$item_custom['FieldValue'] = 'Checked';
										$item_custom['FieldId'] = $id;
										$multiselect_array[] = $item_custom;
									} else {
										$item_custom['FieldName'] = $field["label"] . ': ' . $choices[$c]['value'];
										$item_custom['FieldValue'] = 'Not Checked';
										$item_custom['FieldId'] = $id;
										$multiselect_array[] = $item_custom;
									}
								}							
							break;
							case 'list':								
								$inputs = rgpost("input_{$id}");
								$choices = ($field['choices'] != '') ? $field['choices'] : array();
								$labels_array = array();								
								if(count($choices) > 0) { //If it enable 'Enable multiple columns' option
									foreach($choices as $choice) {
										$label = $field["label"] . ': ' . $choice['value'];
										array_push($labels_array, $label);
									}
								} else {
									for($in = 0; $in < count($inputs); $in++) {
										array_push($labels_array, $field["label"]);
									}
								}								
								$list_array = array();
								for($c = 0; $c < count($inputs); $c++) {
										$row = 1;
										if($c < count($choices)) {
											$item_custom['FieldName'] = $labels_array[$c];
											$row = 1;
										} else {
											$row = $c / count($choices);
											$row = (integer)$row;								
											$item_custom['FieldName'] = $labels_array[$c-(count($choices)*$row)];											
										}										
										$item_custom['FieldValue'] = $inputs[$c];
										$item_custom['FieldId'] = $id;
										if($item_custom['FieldValue'] != '')
										$list_array[] = $item_custom;
								}
							break;							
							case 'radio':								
								$str = rgpost("input_{$id}");								
								$item_custom['FieldName'] = $field["label"];
								$item_custom['FieldValue'] = $str;
								$item_custom['FieldId'] = $id;
							break;
							case 'html':
							case 'section':
							case 'page':
							case 'captcha':
							case 'post_title':
							case 'post_content':
							case 'post_image':
							break;
							default:							
								$item_custom['FieldName'] = $field["label"];
								$temp = rgpost("input_{$id}");
								$val = '';
								
								if(is_array($temp)) 
								{
									if($field["type"] == 'time')
									{									
									if(isset($temp[0]))
										$val = $temp[0];
									if(isset($temp[1]))
										$val .= ':'.$temp[1];
									if(isset($temp[2]))
										$val .= ' '.$temp[2];				
									}
									else
									{
									$val = implode(', ', $temp);
									}
								}
								else
								{
								$val = $temp;
								}
								
								$item_custom['FieldValue'] = $val;
								$item_custom['FieldId'] = $id;							
						}
						//echo $field['type'].'<br>';
						if($field['type'] == 'checkbox') {							
							if(count($checkbox_array)) {								
								foreach($checkbox_array as $cbfield)	{
									$this->customfields[] = $cbfield;
								}
							}							
						} elseif($field['type'] == 'multiselect') {						
							if(count($multiselect_array)) {
								foreach($multiselect_array as $cbfield)	{
									$this->customfields[] = $cbfield;
								}							
							}							
						} elseif($field['type'] == 'list') {						
							if(count($list_array)) {
								foreach($list_array as $cbfield)	{
									$this->customfields[] = $cbfield;
								}							
							}							
						} else {
							if($item_custom['FieldValue'])
							{
								if(count($item_custom)) {
									$hasfield = false;
									if(count($this->customfields)) //Do filter duplicate fields
									{
										foreach($this->customfields as $cfield)
										{
											if($cfield['FieldId'] == $item_custom['FieldId'])
											$hasfield = true;
										}
									}
									if(!$hasfield)
									$this->customfields[] = $item_custom;
								}
							}
						}
						//$this->customfields[] = $item_custom;																	
					}
					break;
			}
		}
	
		// if form didn't pass the total, pick it up from calculated amount
		if ($this->amount < 0)
		   $this->total = $this->amount = 0;
		if ($this->amount > 0 && !isset($item_coupon)) {
			$this->total = $this->amount;
		}
		if ($this->amount > 0 && isset($item_coupon)) {
			if ($item_coupon['type'] == 'percentage' && $item_coupon['amount'] == 100) {
				$this->amount = 0;
			}
		}
		 
	}
		
	/**
	* extract the price from a product field, and multiply it by the quantity
	* @return float
	*/
	public function getProductPrice($form, $field) {
		$price = $qty = 0;
		$isProduct = false;
		$id = $field['id'];
		$item = array();
		$item_custom = array();
		$item_validate = array();
		$item_shipping = array();
		//echo $field["inputType"].'<br>';
		if (!RGFormsModel::is_field_hidden($form, $field, array())) {
			$lead_value = rgpost("input_{$id}");

			$qty_field = GFCommon::get_product_fields_by_type($form, array('quantity'), $id);
			$qty = sizeof($qty_field) > 0 ? rgpost("input_{$qty_field[0]['id']}") : 1;
			
			switch ($field["inputType"]) {
				case 'singleproduct':
				case 'calculation':
					 
					 $pricecalculation = GFCommon::to_number(rgpost("input_{$id}_2"));
					 $qtycalculation = GFCommon::to_number(rgpost("input_{$id}_3"));
					if($qtycalculation > 0 && $pricecalculation != '') {
						$isProduct = true;						
						$item['ItemName'] = $field["label"];
						$item['ItemID'] = $field["id"];
						$item['Quantity'] = $qtycalculation;
						$item['UnitPrice'] = $pricecalculation;
						$item['productField'] = $field["productField"];
						$this->amount = $pricecalculation * $qtycalculation;
						$t = $item['productField'];
						if($t)
						$item['OptionValue'] = rgpost("input_{$t}");						
					}
					break;
				
				case 'singleshipping':				
					$this->shippingCount++;
					$price = GFCommon::to_number($field["basePrice"]);
					$isProduct = true;					
					$item_shipping['ShippingMethod'] = $field["label"];
					$item_shipping['ShippingValue'] = $price;						
					break;
				case 'hiddenproduct':
					$pricehiddenproduct = GFCommon::to_number($field["basePrice"]);
					$qtyhiddenproduct = GFCommon::to_number(rgpost("input_{$id}_3"));

					if($qtyhiddenproduct > 0 && is_numeric($pricehiddenproduct)) {
						$isProduct = true;					
						$item['ItemName'] = $field["label"];
						$item['ItemID'] = $field["id"];						
						$item['Quantity'] = $qtyhiddenproduct;
						$this->amount = $pricehiddenproduct * $qtyhiddenproduct;
						$item['UnitPrice'] = $pricehiddenproduct;
						$item['productField'] = $field["productField"];
						$t = $item['productField'];
						if($t)
						$item['OptionValue'] = rgpost("input_{$t}");
						
						if($price) {
						$item_validate['rule'] = 'price';
						$item_validate['type'] = 'price';
						$item_validate['value'] = $price;
						}
					}
					break;
				case 'donation':
				case 'price':					
					//echo '<pre>';
					//echo GFCommon::evaluate_conditional_logic( ($field['conditionalLogic']['rules'], $form, $lead_value ) );
					//print_r($field['conditionalLogic']['rules']);
					//print_r($field);
					//die();
					$pricedonation = GFCommon::to_number($lead_value);					
					if($qty > 0 && is_numeric($pricedonation)) {
						$isProduct = true;
						$item['ItemName'] = $field["label"];
						$item['ItemID'] = $field["id"];
						$item['Quantity'] = $qty;
						$item['UnitPrice'] = $pricedonation;
						$this->amount = $pricedonation * $qty;
						$item['productField'] = $field["productField"];
						$t = $item['productField'];
						if($t)
						$item['OptionValue'] = rgpost("input_{$t}");
						
						if($pricedonation) {
						$item_validate['rule'] = 'price';
						$item_validate['type'] = 'price';
						$item_validate['value'] = $pricedonation;
						}
					}
					break;
				case 'number':		//This case will handle the 'Quantity' field					
					/*
					$id = $field["productField"];
					$price = GFCommon::to_number(rgpost("input_{$id}_2"));
					$isProduct = true;
					$item['ItemName'] = trim(rgpost("input_{$id}_1"));
					$item['ItemID'] = $id;
					$item['Quantity'] = GFCommon::to_number($lead_value);
					$item['UnitPrice'] = $price;
					$item['productField'] = $field["productField"];
					$t = $item['productField'];
					if($t)
					$item['OptionValue'] = rgpost("input_{$t}");
					
					if($price) {
					$item_validate['rule'] = 'price';
					$item_validate['type'] = 'price';
					$item_validate['value'] = $price;
					}
					*/
					break;
				default:					
					//echo '<pre>';
					//print_r($field);
					//echo GFCommon::get_other_choice_value().'@@@@@';
					//print_r(GF_Field::sanitize_settings_conditional_logic($field['conditionalLogic']));
					//die();
					// handle drop-down lists and radio buttons
					if($field["type"] == 'shipping')
					{
					$this->shippingCount++;
					$priceshipping = GFCommon::to_number($field["basePrice"]);
					$isProduct = true;
					list($name, $priceshipping) = rgexplode('|', $lead_value, 2);
					$item_shipping['ShippingMethod'] = $name;
					$item_shipping['ShippingValue'] = $priceshipping;	
					}					
					elseif (!empty($lead_value)) {
						if($qty > 0) {
							list($name, $price) = rgexplode('|', $lead_value, 2);
							$isProduct = true;
							$item['ItemName'] = $field["label"];
							$item['ItemID'] = $field["id"];
							if (GFCommon::to_number(rgpost("input_{$field['productField']}_3")) != NULL) $item['Quantity'] = GFCommon::to_number(rgpost("input_{$field['productField']}_3"));
							else $item['Quantity'] = $qty;
							//echo $item['Quantity'].'<br/>';
							//echo '<pre>';
							//print_r($field);
							//die();
							$item['UnitPrice'] = $price;
							$item['productField'] = $field["productField"];
							$item['OptionValue'] = $name;
							$choices = $field["choices"];
							$this->amount = $price * $item['Quantity'];
							foreach($choices as $ch)
							{								
								if(GFCommon::to_number($ch['price']) == $price)
								$item['OptionLabel'] = $ch['text'];
							}
						}						
					}
					
					break;
			}
//die();
			// pick up extra costs from any options
			if ($isProduct) {
				$options = GFCommon::get_product_fields_by_type($form, array('option'), $id);
				//echo '<pre>';
				//print_r($options);
				//die();
				foreach($options as $option){
					if (!RGFormsModel::is_field_hidden($form, $option, array())) {
						$option_value = rgpost("input_{$option['id']}");

						if (is_array(rgar($option, 'inputs'))) {
							foreach($option['inputs'] as $input){
								$input_value = rgpost('input_' . str_replace('.', '_', $input['id']));
								$option_info = GFCommon::get_option_info($input_value, $option, true);
								if(!empty($option_info))
									$price += GFCommon::to_number(rgar($option_info, 'price'));
							}
						}
						elseif (!empty($option_value)){
							$option_info = GFCommon::get_option_info($option_value, $option, true);
							$price += GFCommon::to_number(rgar($option_info, 'price'));
						}
					}
				}
			}

			$price *= $qty;
		}

		//echo $field["inputType"].'<br>';
		//die();
		
		//print_r($item_custom);
		//die();
		if(count($item))
		$this->productdetails[$id] = $item;
		if(count($item_custom)) {
			$hasfield = false;
			if(count($this->customfields))
			{
				foreach($this->customfields as $cfield)
				{
					if($cfield['FieldId'] == $item_custom['FieldId'])
					$hasfield = true;
				}
			}
			if(!$hasfield)
			$this->customfields[] = $item_custom;
		}
		//$this->customfields[] = $item_custom;
		if(count($item_validate))
		$this->needtovalidatefields[] = $item_validate;
		if(count($item_shipping))
		$this->shippingfields[] = $item_shipping;
		
		return $price;
	}

	/**
	* clean up credit card number, removing spaces and dashes, so that it should only be digits if correctly submitted
	* @param string $ccNumber
	* @return string
	*/
	private static function cleanCcNumber($ccNumber) {
		return strtr($ccNumber, array(' ' => '', '-' => ''));
	}

	/**
	* check whether we're on the last page of the form
	* @return boolean
	*/
	public function isLastPage() {
		return $this->isLastPageFlag;
	}

	/**
	* check whether CC field is hidden (which indicates that payment is being made another way)
	* @return boolean
	*/
	public function isCcHidden() {
		return $this->isCcHiddenFlag;
	}
	
	/**
	* check whether EC field is hidden (which indicates that payment is being made another way)
	* @return boolean
	*/
	public function isEcHidden() {
		return $this->isEcheckHiddenFlag;
	}

	/**
	* check whether form has any product fields or a recurring payment field (because CC needs something to bill against)
	* @return boolean
	*/
	public function hasPurchaseFields() {
		return $this->hasPurchaseFieldsFlag || !!$this->recurring;
	}

	/**
	* check whether form a recurring payment field
	* @return boolean
	*/
	public function hasRecurringPayments() {
		return !!$this->recurring;
	}
}
