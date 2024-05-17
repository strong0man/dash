<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendance_system";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['students'])) {
    $_SESSION['students'] = [];
}

$showAttendanceCode = false;
$error = "";
$error1 = "";
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reset_attendance'])) {
        $_SESSION['students'] = [];
        unset($_SESSION['attendanceFormVisible']);
        unset($_SESSION['attendanceCode']);
        $message = "Attendance list and session have been reset.";
    }

    if (isset($_POST['generate_code'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        if ($email == 'prof@example.com' && $password == 'password') {
            $code = rand(1000, 9999);
            $_SESSION['attendanceCode'] = $code;
            $showAttendanceCode = true; 
        } else {
            $error = "Incorrect email or password.";
        }
    }

    if (isset($_POST['check_code'])) {
        $enteredCode = $_POST['code'];
        if ($enteredCode == $_SESSION['attendanceCode']) {
            $_SESSION['attendanceFormVisible'] = true;
        } else {
            $error1 = "Incorrect code.";
        }
    }

    if (isset($_POST['submit_attendance'])) {
        $name = $_POST['name'];
        $surname = $_POST['surname'];

        $isDuplicate = false;
        foreach ($_SESSION['students'] as $student) {
            if ($student['name'] === $name && $student['surname'] === $surname) {
                $isDuplicate = true;
                $error1 = "Student with the same name and surname is already registered.";
                break;
            }
        }

        if (!$isDuplicate) {
            $students = $_SESSION['students'];
            $students[] = [
                'name' => $name,
                'surname' => $surname,
                'status' => 'Present',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $_SESSION['students'] = $students;

            $stmt = $conn->prepare("DELETE FROM absents WHERE name=? AND surname=?");
            $stmt->bind_param("ss", $name, $surname);
            $stmt->execute();
            $stmt->close();

            unset($_SESSION['attendanceFormVisible']); 
        }
    }

    if (isset($_POST['save_data'])) {
        $students = $_SESSION['students'];
        foreach ($students as $student) {
            $name = $student['name'];
            $surname = $student['surname'];

            $stmt = $conn->prepare("SELECT * FROM registered_students WHERE name=? AND surname=?");
            $stmt->bind_param("ss", $name, $surname);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt_insert = $conn->prepare("INSERT INTO students (name, surname) VALUES (?, ?)");
                $stmt_insert->bind_param("ss", $name, $surname);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            $stmt->close();
        }
        $message = "Data saved successfully!";
    }
}

$conn->close();

header("Location: index.html");
exit();
?>
