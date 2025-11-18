<?php
function getRecommendations($treeName) {
    $filePath = __DIR__ . "/ml/recommendations.csv";
    
    if (!file_exists($filePath)) {
        return [];
    }

    $file = fopen($filePath, "r");
    $recs = [];

    fgetcsv($file); // skip header row

    while (($row = fgetcsv($file)) !== false) {

        $ante = strtolower($row[0]);      // antecedents
        $cons = strtolower($row[1]);      // consequents
        $conf = floatval($row[3]);        // confidence

        // match the current seedling with antecedents
        if (strpos($ante, strtolower($treeName)) !== false) {
            $recs[] = [
                "ante" => $ante,
                "cons" => $cons,
                "confidence" => $conf
            ];
        }
    }

    fclose($file);
    return $recs;
}
?>
