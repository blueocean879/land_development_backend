<?php
Class Model_builder extends CI_Model
{
	function getBuilders()
	{
		$this->db->order_by('builder_name', 'asc');
		$query  = $this->db->get('builders');
		$result = array();
		if ($query->num_rows() > 0)
		{
			$result = $query->result();
		}
		return $result;
	}

	function getBuilder($builder_id)
	{
		$query  = $this->db->get_where('builders', array('builders.builder_id' => $builder_id));
		$result = null;
		if ($query->num_rows() > 0)
		{
			$result = $query->row();
		}
		return $result;
	}

	function addBuilder($form_data)
	{
		$fields         = $this->getFields();
		$builder_fields = array();
		foreach($fields as $field_id){
			if(isset($form_data[$field_id])){
				$builder_fields[$field_id] = $form_data[$field_id];
			}
		}
		$this->db->insert('builders', $builder_fields);
		return $this->db->insert_id();
	}

	function updateBuilder($builder_id, $form_data)
	{
		$fields         = $this->getFields();
		$builder_fields = array();
		foreach($fields as $field_id){
			if(isset($form_data[$field_id])){
				$builder_fields[$field_id] = $form_data[$field_id];
			}
		}
		$this->db->where('builders.builder_id', $builder_id);
		return $this->db->update('builders', $builder_fields);
	}

	function duplicateName($builder_name, $builder_id = null)
	{
		$this->db->like('builder_name', $builder_name, 'none');
		if($builder_id){
			$this->db->where('builders.builder_id !=', $builder_id);
		}
		$query  = $this->db->get('builders');
		$result = ($query? $query->row(): FALSE);
		return (!empty($result));
	}

	function deleteBuilder($builder_id)
	{
		$this->db->where('builders.builder_id', $builder_id);
		return $this->db->delete('builders');
	}

	private function getFields()
	{
		return array(
			'builder_name',
			'builder_website',
			'builder_email',
			'builder_telephone'
		);
	}

}
?>