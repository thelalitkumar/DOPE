<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Candidate_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    // Get candidates by status
    public function get_candidates_by_status($status) {
        $this->db->where('status', $status);
        $query = $this->db->get('users');
        return $query->result();
    }
    
    // Get candidate count by status
    public function get_candidate_count($status) {
        $this->db->where('status', $status);
        return $this->db->count_all_results('users');
    }
    
    // Get candidate by ID
    public function get_candidate($id) {
        $this->db->where('id', $id);
        $query = $this->db->get('users');
        return $query->row();
    }
    
    // Search candidates by roll number and status
    public function search_candidates($roll_no, $status) {
        $this->db->like('roll_no', $roll_no);
        $this->db->where('status', $status);
        $query = $this->db->get('users');
        return $query->result();
    }
    
    // Update candidate status
    public function update_status($id, $status) {
        $this->db->where('id', $id);
        return $this->db->update('users', ['status' => $status]);
    }
    
    // Save offer letter
    public function save_offer_letter($data) {
        $this->db->insert('offer_letters', $data);
        return $this->db->insert_id();
    }
    
    // Get offer letter by user ID
    public function get_offer_letter($user_id) {
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('offer_letters');
        return $query->row();
    }
}