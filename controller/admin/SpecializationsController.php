<?php
require_once('../../models/admin/Specializations.php');

function handleSpecializationsRequest($conn) {

    $specialization_edit = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_POST['btnAdd'])) {
            createSpecialization($conn, $_POST['name'], $_POST['description']);
            header("Location: ../admin/manage_specializations.php");
            exit();
        }

        if (isset($_POST['btnEdit'])) {
            updateSpecialization(
                $conn,
                $_POST['specialization_id'],
                $_POST['name'],
                $_POST['description']
            );
            header("Location: ../admin/manage_specializations.php");
            exit();
        }

        if (isset($_POST['btnDelete'])) {
            deleteSpecialization($conn, $_POST['specialization_id']);
            header("Location: ../admin/manage_specializations.php");
            exit();
        }
    }

    if (isset($_GET['action']) && $_GET['action'] === 'edit') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $specialization_edit = getSpecializationById($conn, $id);
        }
    }

    return $specialization_edit;
}
?>