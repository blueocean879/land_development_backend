<?php
Class Model_amenity_type extends CI_Model
{
	function getAmenityTypes()
	{
		$this->db->order_by('amenity_type_name', 'asc');
		$query  = $this->db->get('amenity_types');
		$result = array();
		if ($query->num_rows() > 0)
		{
			$result = $query->result(); 
		}
		return $result;
	}

	function getAmenityType($amenity_type_id)
	{
		$query  = $this->db->get_where('amenity_types', array('amenity_type_id' => $amenity_type_id));
		$result = null;
		if ($query->num_rows() > 0)
		{
			$result = $query->row(); 
		}
		return $result;
	}

}
?>