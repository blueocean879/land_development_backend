<?php
Class Model_builder_sales_person extends CI_Model
{
	public function getSalesPeople($builder_id, $links = FALSE)
	{
		$this->db->where('builder_sales_people.builder_id', $builder_id);
		$this->db->order_by('name', $builder_id);
		$query   = $this->db->get('builder_sales_people');
		$results = ($query->num_rows() > 0)? $query->result(): array();
		if($links){
			$active_packages   = $this->getActiveSalesPeoplePackages($builder_id);
			$pending_packages  = $this->getToApproveSalesPeoplePackages($builder_id);
			$active_packages   = count($active_packages)? array_combine(array_map(function($v){return $v->builder_sales_person_id;}, $active_packages), array_map(function($v){return $v->total_packages;}, $active_packages)): array();
			$pending_packages  = count($pending_packages)? array_combine(array_map(function($v){return $v->builder_sales_person_id;}, $pending_packages), array_map(function($v){return $v->total_packages_approval;}, $pending_packages)): array();
			foreach($results as $row){
				$row->packages = @$active_packages[$row->builder_sales_person_id] + @$pending_packages[$row->builder_sales_person_id];
			}
		}
		return $results;
	}

	public function getActiveSalesPeoplePackages($builder_id)
	{
		$this->db->select('builder_sales_people.builder_sales_person_id, COUNT(house_lot_packages.house_lot_package_id) AS total_packages');
		$this->db->join('house_lot_packages', 'house_lot_packages.builder_sales_person_id = builder_sales_people.builder_sales_person_id');
		$this->db->where('house_lot_packages.active', 1);
		$this->db->where('builder_sales_people.builder_id', $builder_id);
		$this->db->group_by('builder_sales_people.builder_sales_person_id');
		$query  = $this->db->get('builder_sales_people');
		return ($query->num_rows() > 0)? $query->result(): array();
	}

	public function getToApproveSalesPeoplePackages($builder_id)
	{
		$this->db->select('builder_sales_people.builder_sales_person_id, COUNT(house_lot_package_approvals.house_lot_package_approval_id) AS total_packages_approval');
		$this->db->join('house_lot_package_approvals', 'house_lot_package_approvals.builder_sales_person_id = builder_sales_people.builder_sales_person_id');
		$this->db->where('builder_sales_people.builder_id', $builder_id);
		$this->db->where('house_lot_package_approvals.status', 'Pending');

		$query  = $this->db->get('builder_sales_people');
		return ($query->num_rows() > 0)? $query->result(): array();
	}

	public function getSalesPerson($builder_sales_person_id)
	{
		$this->db->where('builder_sales_people.builder_sales_person_id', $builder_sales_person_id);
		$query  = $this->db->get('builder_sales_people');
		return ($query->num_rows() > 0)? $query->row(): FALSE;
	}

	public function addSalesPerson($builder_id, $form_data)
	{
		$fields         = $this->getFields();
		$builder_fields = array('builder_id' => $builder_id);
		foreach($fields as $field_id){
			if(isset($form_data[$field_id])){
				$builder_fields[$field_id] = $form_data[$field_id];
			}
		}
		if(isset($form_data['image_name'])){
			$image_name_url = $form_data['image_name'];
			if($image_name_url && filter_var($image_name_url, FILTER_VALIDATE_URL)){
				$new_file_path = BASEPATH."../../mpvs/images/builder_images/";
				if (!file_exists($new_file_path)) {
					mkdir($new_file_path, 0777, true);
				}
				$file_name    = basename($image_name_url);
				$new_filename = $this->uniqueFileName($new_file_path, $file_name);
				file_put_contents($new_file_path.$new_filename, file_get_contents($image_name_url));
				$builder_fields['picture'] = $new_filename;
			}
		}
		$this->db->insert('builder_sales_people', $builder_fields);
		return $this->db->insert_id();
	}

	public function updateSalesPerson($sales_person_id, $form_data)
	{
		$sales_person = $this->getSalesPerson($sales_person_id);
		$fields         = $this->getFields();
		$builder_fields = array();
		foreach($fields as $field_id){
			if(isset($form_data[$field_id])){
				$builder_fields[$field_id] = $form_data[$field_id];
			}
		}
		if(isset($form_data['image_name'])){
			$image_name_url = $form_data['image_name'];
			if($image_name_url && filter_var($image_name_url, FILTER_VALIDATE_URL)){
				$new_file_path = BASEPATH."../../mpvs/images/builder_images/";
				if (!file_exists($new_file_path)) {
					mkdir($new_file_path, 0777, true);
				}
				if(!empty($sales_person->picture) && file_exists($new_file_path.$sales_person->picture)){
					unlink($new_file_path.$sales_person->picture);
				}
				$file_name    = basename($image_name_url);
				$new_filename = $this->uniqueFileName($new_file_path, $file_name);
				file_put_contents($new_file_path.$new_filename, file_get_contents($image_name_url));
				$builder_fields['picture'] = $new_filename;
			}
		}
		$this->db->where('builder_sales_person_id', $sales_person_id);
		return $this->db->update('builder_sales_people', $builder_fields);
	}

	function deleteImage($sales_person_id)
	{
		$sales_person = $this->getSalesPerson($sales_person_id);
		$file_path    = BASEPATH."../../mpvs/images/builder_images/";
		$image_path   = $file_path.$sales_person->picture;

		// remove the images from folder
		if (!empty($sales_person->picture) && file_exists($image_path)) {
			unlink($image_path);
		}
		$this->db->where('builder_sales_person_id', $sales_person_id);
		return $this->db->update('builder_sales_people', array('picture' => ''));
	}

	public function deleteSalesPerson($sales_person_id)
	{
		$this->deleteImage($sales_person_id);

		$this->db->where('builder_sales_person_id', $sales_person_id);
		return $this->db->delete('builder_sales_people');
	}

	public function canDeleteSalesPerson($builder_sales_user_id)
	{
		$this->db->where('builder_sales_person_id', $builder_sales_user_id);
		$this->db->where('active', 1);
		$query1 = $this->db->get('house_lot_packages');
		if($query1->num_rows() > 0){
			return FALSE;
		}
		$this->db->where('status', 'Pending');
		$this->db->where('builder_sales_person_id', $builder_sales_user_id);
		$query2 = $this->db->get('house_lot_package_approvals');
		if($query2->num_rows() > 0){
			return FALSE;
		}
		return TRUE;
	}

	private function getFields()
	{
		return array(
			'name',
			'telephone',
			'mobilephone',
			'email'
		);
	}

	public function uniqueFileName($file_path, $file_name)
	{
		$counter       = 1;
		$new_file_name = $file_name;
		while (file_exists($file_path.$new_file_name)) {
			list($name, $extension) = explode('.', $file_name);
			$new_file_name = "{$name}({$counter}).{$extension}";
			$counter++;
		}
		return $new_file_name;
	}

}
?>