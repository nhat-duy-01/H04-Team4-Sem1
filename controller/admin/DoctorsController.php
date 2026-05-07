<?php
require_once('../../models/admin/doctors.php');

function handleDoctorsRequest($conn) {
    $doctor_edit = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // ===== ADD =====
        if (isset($_POST['btnAdd'])) {

            $username      = $_POST['username'] ?? '';
            $email         = $_POST['email'] ?? '';
            $password      = $_POST['password'] ?? '';
            $fullname      = $_POST['fullname'] ?? '';
            $phone         = $_POST['phone'] ?? '';
            $contactNumber = $_POST['contactNumber'] ?? '';
            $specializations = $_POST['specializations'] ?? []; 

            if ($username && $email && $password && $fullname && $phone) {
                createDoctor($conn, $username, $email, $password, $fullname, $phone, $contactNumber, $specializations);
                header("Location: ../../views/admin/manage_doctors.php");
                exit();
            } else {
                echo "All fields are required.";
            }
        }

        // ===== EDIT =====
        if (isset($_POST['btnEdit'])) {

            $doctor_id     = (int)$_POST['doctor_id'];
            $fullname      = $_POST['fullname'] ?? '';
            $email         = $_POST['email'] ?? '';
            $phone         = $_POST['phone'] ?? '';
            $contactNumber = $_POST['contactNumber'] ?? '';
            $specializations = $_POST['specializations'] ?? []; 

            if ($doctor_id && $fullname && $email && $phone) {
                updateDoctor($conn, $doctor_id, $fullname, $email, $phone, $contactNumber, $specializations);
                header("Location: ../../views/admin/manage_doctors.php");
                exit();
            } else {
                echo "All fields are required.";
            }
        }

        // ===== DELETE =====
        if (isset($_POST['btnDelete'])) {
            $doctor_id = (int)$_POST['doctor_id'];
            $doctor = getDoctorById($conn, $doctor_id);

            if ($doctor && $doctor['User_ID'] > 1) {
                deleteDoctor($conn, $doctor_id);
            }

            header("Location: ../../views/admin/manage_doctors.php");
            exit();
        }
    }

    // ===== LOAD EDIT =====
    if (isset($_GET['action']) && $_GET['action'] === 'edit') {
        $doctor_id = (int)($_GET['id'] ?? 0);
        if ($doctor_id > 0) {
            $doctor_edit = getDoctorById($conn, $doctor_id);
        }
    }

    return $doctor_edit;
}
?>