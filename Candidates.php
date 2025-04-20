<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Candidates extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Candidate_model');
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->library('session');
        
        // Set default phone number for OTP if not set
        if (!$this->session->userdata('phone_number')) {
            $this->session->set_userdata('phone_number', '8708708787');
        }
    }
    
    public function test() {
        // Make sure session is started
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Create an array to hold all session information
        $session_info = array();
        
        // Get native PHP session data
        $session_info['php_session'] = $_SESSION;
        
        // Get session ID
        $session_info['session_id'] = session_id();
        
        // Get session status
        switch (session_status()) {
            case PHP_SESSION_DISABLED:
                $session_info['session_status'] = 'Sessions are disabled';
                break;
            case PHP_SESSION_NONE:
                $session_info['session_status'] = 'Sessions are enabled but none exists';
                break;
            case PHP_SESSION_ACTIVE:
                $session_info['session_status'] = 'Sessions are enabled and one exists';
                break;
        }
        
        // Get session save path
        $session_info['save_path'] = session_save_path();
        
        // Get session cookie parameters
        $session_info['cookie_params'] = session_get_cookie_params();
        
        // Get session cookies
        $session_info['cookies'] = $_COOKIE;
        
        // Get CodeIgniter specific session info if available
        if (isset($this->session)) {
            $session_info['ci_session_id'] = $this->session->session_id;
            
            // Get session data without using userdata() method
            // This is a bit of a hack to access CodeIgniter's internal session data
            $reflection = new ReflectionClass($this->session);
            
            if ($reflection->hasProperty('userdata')) {
                $property = $reflection->getProperty('userdata');
                $property->setAccessible(true);
                $session_info['ci_userdata'] = $property->getValue($this->session);
            }
            
            // Get session configuration
            $session_info['ci_config'] = array();
            foreach ($this->config->config as $key => $value) {
                if (strpos($key, 'sess_') === 0) {
                    $session_info['ci_config'][$key] = $value;
                }
            }
        }
        
        // Display the information
        echo "<h1>Complete Session Information Array</h1>";
        echo "<pre>";
        print_r($session_info);
        echo "</pre>";
        
        return $session_info;
    }
    
    // Get masked phone number for OTP display
    private function getMaskedPhone() {
        $phone = $this->session->userdata('phone_number');
        if ($phone) {
            return 'XXXXXX' . substr($phone, -4);
        }
        return '';
    }
    
    // Main view with tabs
    public function index() {
        $data['active_tab'] = $this->input->get('tab') ? $this->input->get('tab') : 'pending';
        $data['pending_count'] = $this->Candidate_model->get_candidate_count(0);
        $data['interviewed_count'] = $this->Candidate_model->get_candidate_count(1);
        $data['selected_count'] = $this->Candidate_model->get_candidate_count(2);
        
        $roll_no = $this->input->get('roll_no');
        
        if ($data['active_tab'] === 'pending') {
            $status = 0;
        } elseif ($data['active_tab'] === 'interviewed') {
            $status = 1;
        } else { // selected
            $status = 2;
        }
        
        if ($roll_no) {
            $data['candidates'] = $this->Candidate_model->search_candidates($roll_no, $status);
        } else {
            $data['candidates'] = $this->Candidate_model->get_candidates_by_status($status);
        }
        
        // Flash messages for main page feedback
        $data['status_message'] = $this->session->flashdata('status_message');
        $data['status_type'] = $this->session->flashdata('status_type');
        
        $this->load->view('candidates/index', $data);
    }
    
    // Change candidate status
    public function change_status() {
        $candidate_id = $this->input->post('candidate_id');
        $new_status = $this->input->post('new_status');
        
        $result = $this->Candidate_model->update_status($candidate_id, $new_status);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Candidate status updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update candidate status']);
        }
    }
    
    // Upload offer letter
    public function upload_offer() {
        $candidate_id = $this->input->post('candidate_id');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['offer_letter'])) {
            $fileTmpPath = $_FILES['offer_letter']['tmp_name'];
            $fileName = $_FILES['offer_letter']['name'];
            $fileSize = $_FILES['offer_letter']['size'];
            $fileType = $_FILES['offer_letter']['type'];
            
            // Check if file was uploaded
            if (empty($fileTmpPath)) {
                echo json_encode(['status' => 'error', 'message' => 'Please select a file to upload']);
                return;
            }
            
            // Get file extension
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            // Sanitize file name
            $newFileName = uniqid() . '.' . $fileExtension;
            
            $allowedExtensions = ['pdf', 'doc', 'docx'];
            if (in_array($fileExtension, $allowedExtensions)) {
                // Upload path
                $uploadFileDir = './uploads/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                
                // Move file
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Prepare data for database
                    $data = array(
                        'user_id' => $candidate_id,
                        'filename' => $newFileName,
                        'original_filename' => $fileName,
                        'file_path' => $dest_path,
                        'file_type' => $fileType,
                        'comments' => $this->input->post('comments')
                    );
                    
                    // Save to session for later use after OTP verification
                    $this->session->set_userdata('temp_offer_letter_data', $data);
                    
                    // Send OTP
                    $otp = $this->sendOTP();
                    
                    // Return success for AJAX
                    echo json_encode([
                        'status' => 'success', 
                        'message' => 'OTP sent successfully',
                        'phone' => $this->getMaskedPhone()
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error moving file to upload directory']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Only .pdf, .doc, and .docx files are allowed']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
        }
    }
    
    // Generate and send OTP
    public function sendOTP() {
        $otp = rand(100000, 999999);
        $this->session->set_userdata('offer_letter_otp', $otp);
        $this->session->set_userdata('otp_time', time());
        
        // In a real application, you would send this OTP via SMS/Email
        return $otp;
    }
    
    // Resend OTP
    public function resend_otp() {
        $otp = $this->sendOTP();
        echo json_encode([
            'status' => 'success', 
            'message' => 'OTP resent successfully',
            'phone' => $this->getMaskedPhone()
        ]);
    }
    
    // Verify OTP and complete offer letter upload
    public function verify_otp() {
        $entered_otp = $this->input->post('entered_otp');
        $stored_otp = $this->session->userdata('offer_letter_otp');
        
        if (empty($entered_otp)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter the OTP']);
            return;
        }
        
        if ($entered_otp == $stored_otp) {
            // OTP verified, save offer letter data from session
            $offer_letter_data = $this->session->userdata('temp_offer_letter_data');
            
            if ($offer_letter_data) {
                $offer_id = $this->Candidate_model->save_offer_letter($offer_letter_data);
                
                // Update candidate status to selected (2)
                $this->Candidate_model->update_status($offer_letter_data['user_id'], 2);
                
                $this->session->unset_userdata('temp_offer_letter_data');
                $this->session->unset_userdata('offer_letter_otp');
                
                echo json_encode(['status' => 'success', 'message' => 'Offer letter uploaded successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No offer letter data found']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
        }
    }
    
    // View offer letter
    public function view_offer($candidate_id) {
        $offer_letter = $this->Candidate_model->get_offer_letter($candidate_id);
        
        if ($offer_letter) {
            // For PDF files, display in browser
            if (strpos($offer_letter->file_type, 'pdf') !== false) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $offer_letter->original_filename . '"');
                readfile($offer_letter->file_path);
            } else {
                // For other file types, force download
                header('Content-Type: ' . $offer_letter->file_type);
                header('Content-Disposition: attachment; filename="' . $offer_letter->original_filename . '"');
                readfile($offer_letter->file_path);
            }
        } else {
            echo "Offer letter not found.";
        }
    }
   
}