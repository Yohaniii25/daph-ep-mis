<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../../../index.php");
    exit();
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: white;
        margin: 0 auto;
    }
    
    .info-card {
        border: none;
        border-radius: 15px;
        transition: all 0.3s ease;
    }
    
    .info-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .info-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #6c757d;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: #2c3e50;
        margin-top: 5px;
    }
    
    .stat-box {
        background: white;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .skill-tag {
        display: inline-block;
        padding: 4px 10px;
        background: #e9ecef;
        border-radius: 15px;
        font-size: 0.7rem;
        margin: 3px;
    }
    
    .timeline-item {
        padding-left: 25px;
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 5px;
        top: 5px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #667eea;
    }
    
    .timeline-item::after {
        content: '';
        position: absolute;
        left: 8px;
        top: 13px;
        bottom: -20px;
        width: 2px;
        background: #e9ecef;
    }
    
    .timeline-item:last-child::after {
        display: none;
    }
</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4 pb-5">
        
        <!-- Profile Header -->
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body p-4 text-white text-center">
                <div class="profile-avatar mb-3">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h3 class="fw-bold mb-1">Kumari Silva</h3>
                <p class="mb-0 opacity-75">Veterinary Field Officer</p>
                <p class="small opacity-75">Ampara District</p>
            </div>
        </div>
        
        <!-- Statistics Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <i class="bi bi-calendar-check fs-3 text-primary"></i>
                    <div class="stat-number text-primary">24</div>
                    <small class="text-muted">Leave Balance</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <i class="bi bi-check-circle fs-3 text-success"></i>
                    <div class="stat-number text-success">156</div>
                    <small class="text-muted">Tasks Done</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <i class="bi bi-star fs-3 text-warning"></i>
                    <div class="stat-number text-warning">4.8</div>
                    <small class="text-muted">Rating</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <i class="bi bi-award fs-3 text-danger"></i>
                    <div class="stat-number text-danger">03</div>
                    <small class="text-muted">Awards</small>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Left Column - Personal Info -->
            <div class="col-lg-4">
                <div class="card info-card shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-person-badge me-2 text-primary"></i>Personal Details</h6>
                        
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="info-label">Full Name</div>
                            <div class="info-value">Kumari Silva</div>
                        </div>
                        
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="info-label">Employee ID</div>
                            <div class="info-value">EMP/2024/001</div>
                        </div>
                        
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="info-label">Designation</div>
                            <div class="info-value">Veterinary Field Officer</div>
                        </div>
                        
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="info-label">Email</div>
                            <div class="info-value">kumari.s@daph.gov.lk</div>
                        </div>
                        
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="info-label">Phone</div>
                            <div class="info-value">077 123 4567</div>
                        </div>
                        
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="info-label">District</div>
                            <div class="info-value">Ampara</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="info-label">Joined Date</div>
                            <div class="info-value">January 15, 2024</div>
                        </div>
                    </div>
                </div>
                
                <!-- Skills -->
                <div class="card info-card shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-book me-2 text-primary"></i>Skills</h6>
                        <div>
                            <span class="skill-tag">Animal Health</span>
                            <span class="skill-tag">Vaccination</span>
                            <span class="skill-tag">Farm Management</span>
                            <span class="skill-tag">Breeding</span>
                            <span class="skill-tag">Disease Control</span>
                            <span class="skill-tag">AI Procedures</span>
                        </div>
                        
                        <hr>
                        
                        <h6 class="fw-bold mb-3"><i class="bi bi-translate me-2 text-primary"></i>Languages</h6>
                        <div>
                            <span class="skill-tag">Sinhala</span>
                            <span class="skill-tag">English</span>
                            <span class="skill-tag">Tamil</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Activity & More -->
            <div class="col-lg-8">
                <!-- Recent Activity -->
                <div class="card info-card shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Activity</h6>
                        
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold small">Submitted Monthly Report</span>
                                <span class="small text-muted">2 hours ago</span>
                            </div>
                            <p class="small text-muted mb-0">March 2026 production report</p>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold small">Field Visit Completed</span>
                                <span class="small text-muted">Yesterday</span>
                            </div>
                            <p class="small text-muted mb-0">Vaccination drive at Ampara South</p>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold small">Leave Approved</span>
                                <span class="small text-muted">3 days ago</span>
                            </div>
                            <p class="small text-muted mb-0">Casual leave for 2 days</p>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold small">Training Attended</span>
                                <span class="small text-muted">1 week ago</span>
                            </div>
                            <p class="small text-muted mb-0">Animal Health Management Workshop</p>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Info -->
                <div class="card info-card shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Additional Information</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="info-label">Supervisor</div>
                                <div class="info-value small">Dr. Mrs L. Dujiththera</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="info-label">Emergency Contact</div>
                                <div class="info-value small">Ruwan Perera - 077 765 4321</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="info-label">Office Location</div>
                                <div class="info-value small">Ampara Veterinary Office</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="info-label">Work Schedule</div>
                                <div class="info-value small">Mon-Fri: 8:30 AM - 4:30 PM</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>