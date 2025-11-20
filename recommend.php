<?php
require_once "db.php";

function parse_frozenset($str) {
    // Extracts the value from frozenset({'name'})
    if (preg_match("/\{\s*'([^']+)'\s*\}/", $str, $m)) {
        return $m[1];
    }
    return trim($str);
}

function get_recommendations(string $selectedName): array {
    global $db;

    $file = "ml/recommendations.csv";

    if (!file_exists($file)) {
        return [];
    }

    $csv = fopen($file, "r");
    if (!$csv) {
        return [];
    }

    $headers = fgetcsv($csv); // read header line
    $recommendations = [];

    while (($row = fgetcsv($csv)) !== false) {
        $data = array_combine($headers, $row);

        // Use correct column names and parse frozenset
        $antecedent = isset($data['antecedents']) ? parse_frozenset($data['antecedents']) : '';
        $consequent = isset($data['consequents']) ? parse_frozenset($data['consequents']) : '';

        if (strtolower(trim($antecedent)) === strtolower(trim($selectedName))) {
            // Find matching recommended product in DB
            $stmt = mysqli_prepare(
                $db,
                "SELECT TREESPECIES_ID, COMMON_NAME, PRICE, IMAGE
                 FROM treespecies
                 WHERE LOWER(COMMON_NAME) = LOWER(?)
                 LIMIT 1"
            );

            mysqli_stmt_bind_param($stmt, "s", $consequent);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row_db = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if ($row_db) {
                $recommendations[] = [
                    "id"         => $row_db["TREESPECIES_ID"],
                    "name"       => $row_db["COMMON_NAME"],
                    "price"      => $row_db["PRICE"],
                    "image"      => $row_db["IMAGE"],
                    "confidence" => isset($data['confidence']) ? floatval($data['confidence']) : null
                ];
            }
        }
    }

    fclose($csv);
    return $recommendations;
}
