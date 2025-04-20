<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .tab-buttons {
            display: flex;
            width: 100%;
            margin-bottom: 20px;
            gap: 15px; /* Add margin between tab buttons */
        }
        
        .tab-button {
            flex: 1;
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            cursor: pointer;
            text-decoration: none;
            color: #495057;
            border-radius: 4px; /* Add border radius to each button */
        }
        
        .tab-button.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .search-count-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .count-badge {
            background-color: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .search-form {
            display: flex;
        }
        
        .search-form input {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .search-form button {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        
        /* Added styles for status messages */
        .status-message {
            margin-top: 10px;
            padding: 10px 15px;
            border-radius: 4px;
            display: none;
        }
        
        .status-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Candidates Management</h2>
        
        <!-- Status message container for main page -->
        <div id="mainPageStatus" class="status-message"></div>
        
        <!-- Tab Buttons -->
        <div class="tab-buttons">
            <a href="<?php echo base_url('candidates?tab=pending'); ?>" 
               class="tab-button <?php echo $active_tab == 'pending' ? 'active' : ''; ?>">
                Pending
            </a>
            <a href="<?php echo base_url('candidates?tab=interviewed'); ?>" 
               class="tab-button <?php echo $active_tab == 'interviewed' ? 'active' : ''; ?>">
                Interviewed
            </a>
            <a href="<?php echo base_url('candidates?tab=selected'); ?>" 
               class="tab-button <?php echo $active_tab == 'selected' ? 'active' : ''; ?>">
                Selected
            </a>
        </div>
        
        <!-- Search and Count Container -->
        <div class="search-count-container">
            <!-- Search form on the left -->
            <form class="search-form" action="<?php echo base_url('candidates'); ?>" method="get">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                <input type="text" class="form-control" name="roll_no" placeholder="Search by Roll No" 
                       value="<?php echo $this->input->get('roll_no'); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            
            <!-- Count display on the right -->
            <div class="count-badge">
                <?php 
                if ($active_tab == 'pending') {
                    echo "Count: " . $pending_count;
                } elseif ($active_tab == 'interviewed') {
                    echo "Count: " . $interviewed_count;
                } else {
                    echo "Count: " . $selected_count;
                }
                ?>
            </div>
        </div>
        
        <!-- Candidates List -->
        <div class="candidates-list">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($candidates)): ?>
                        <?php foreach ($candidates as $candidate): ?>
                            <tr>
                                <td><?php echo $candidate->roll_no; ?></td>
                                <td><?php echo $candidate->name; ?></td>
                                <td><?php echo $candidate->email; ?></td>
                                <td>
                                    <?php if ($active_tab === 'pending'): ?>
                                        <button class="btn btn-sm btn-primary change-status" 
                                                data-id="<?php echo $candidate->id; ?>" 
                                                data-status="1">
                                            Mark Interviewed
                                        </button>
                                    <?php elseif ($active_tab === 'interviewed'): ?>
                                        <button class="btn btn-sm btn-success upload-offer" 
                                                data-id="<?php echo $candidate->id; ?>">
                                            Upload Offer Letter
                                        </button>
                                    <?php elseif ($active_tab === 'selected'): ?>
                                        <a href="<?php echo base_url('candidates/view_offer/' . $candidate->id); ?>" 
                                           class="btn btn-sm btn-info" target="_blank">
                                            View Offer Letter
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No candidates found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal for uploading offer letter and OTP verification -->
    <div class="modal fade" id="offerLetterModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Offer Letter</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Status message for offer letter modal -->
                    <div id="offerLetterStatus" class="status-message"></div>
                    
                    <form id="offerLetterForm" enctype="multipart/form-data">
                        <input type="hidden" name="candidate_id" id="candidateId">
                        
                        <div class="form-group">
                            <label for="offerLetter">Offer Letter (PDF, DOC, DOCX)</label>
                            <input type="file" class="form-control-file" id="offerLetter" name="offer_letter" required>
                            <div id="offerLetterError" class="form-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="comments">Comments</label>
                            <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                            <div id="commentsError" class="form-error"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Upload & Verify</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- OTP Verification Modal -->
    <div class="modal fade" id="otpModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">OTP Verification</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Status message for OTP modal -->
                    <div id="otpStatus" class="status-message"></div>
                    
                    <p>An OTP has been sent to <span id="phoneNumber"></span></p>
                    
                    <div class="form-group">
                        <label for="otpInput">Enter OTP</label>
                        <input type="text" class="form-control" id="otpInput" maxlength="6">
                        <div id="otpError" class="form-error"></div>
                    </div>
                    
                    <button id="verifyOtpBtn" class="btn btn-primary">Verify OTP</button>
                    <button id="resendOtpBtn" class="btn btn-link">Resend OTP</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Function to show status message
            function showStatus(elementId, message, isSuccess) {
                var statusElement = $('#' + elementId);
                statusElement.removeClass('status-success status-error');
                statusElement.addClass(isSuccess ? 'status-success' : 'status-error');
                statusElement.text(message);
                statusElement.show();
                
                // Auto hide after 5 seconds if it's a success message
                if (isSuccess && elementId !== 'mainPageStatus') {
                    setTimeout(function() {
                        statusElement.hide();
                    }, 5000);
                }
            }
            
            // Function to clear form errors
            function clearErrors() {
                $('.form-error').hide();
            }
            
            // Function to show form error
            function showError(elementId, message) {
                var errorElement = $('#' + elementId);
                errorElement.text(message);
                errorElement.show();
            }
            
            // Change status button click
            $('.change-status').click(function() {
                var candidateId = $(this).data('id');
                var newStatus = $(this).data('status');
                
                $.ajax({
                    url: '<?php echo base_url('candidates/change_status'); ?>',
                    type: 'POST',
                    data: {
                        candidate_id: candidateId,
                        new_status: newStatus
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showStatus('mainPageStatus', response.message, true);
                            // Reload the page after a delay
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showStatus('mainPageStatus', response.message, false);
                        }
                    }
                });
            });
            
            // Upload offer letter button click
            $('.upload-offer').click(function() {
                var candidateId = $(this).data('id');
                $('#candidateId').val(candidateId);
                clearErrors();
                $('#offerLetterStatus').hide();
                $('#offerLetterModal').modal('show');
            });
            
            // Offer letter form submit
            $('#offerLetterForm').submit(function(e) {
                e.preventDefault();
                clearErrors();
                
                var formData = new FormData(this);
                
                $.ajax({
                    url: '<?php echo base_url('candidates/upload_offer'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#offerLetterModal').modal('hide');
                            $('#phoneNumber').text(response.phone);
                            $('#otpStatus').hide();
                            $('#otpError').hide();
                            $('#otpInput').val('');
                            $('#otpModal').modal('show');
                        } else {
                            showStatus('offerLetterStatus', response.message, false);
                        }
                    }
                });
            });
            
            // Verify OTP button click
            $('#verifyOtpBtn').click(function() {
                var enteredOtp = $('#otpInput').val();
                
                if (!enteredOtp) {
                    showError('otpError', 'Please enter the OTP');
                    return;
                }
                
                $.ajax({
                    url: '<?php echo base_url('candidates/verify_otp'); ?>',
                    type: 'POST',
                    data: {
                        entered_otp: enteredOtp
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#otpModal').modal('hide');
                            showStatus('mainPageStatus', response.message, true);
                            // Reload the page after a delay
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showError('otpError', response.message);
                        }
                    }
                });
            });
            
            // Resend OTP button click
            $('#resendOtpBtn').click(function() {
                $.ajax({
                    url: '<?php echo base_url('candidates/resend_otp'); ?>',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showStatus('otpStatus', response.message, true);
                        } else {
                            showStatus('otpStatus', response.message, false);
                        }
                    }
                });
            });
            
            // Clear errors when input changes
            $('#otpInput').on('input', function() {
                $('#otpError').hide();
            });
            
            $('#offerLetter').on('change', function() {
                $('#offerLetterError').hide();
            });
            
            $('#comments').on('input', function() {
                $('#commentsError').hide();
            });
        });
    </script>
</body>
</html>