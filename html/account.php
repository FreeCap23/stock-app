<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST["username"])
        ? htmlspecialchars($_POST["username"])
        : "";
    $password = isset($_POST["password"])
        ? htmlspecialchars($_POST["password"])
        : "";
    $action = isset($_POST["action"]) ? htmlspecialchars($_POST["action"]) : "";
}
