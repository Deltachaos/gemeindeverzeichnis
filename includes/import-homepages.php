<?php
/**
 * Import data from Wikidata query:
 * SELECT DISTINCT ?itemLabel ?website ?zip
 * WHERE {
 * ?item wdt:P31/wdt:P279* wd:Q262166
 * OPTIONAL { ?item wdt:P856 ?website }
 * OPTIONAL { ?item wdt:P281 ?zip }
 * SERVICE wikibase:label { bd:serviceParam wikibase:language "de" }
 * }
 * ORDER BY ?itemLabel
 */
if (($handle = fopen("data-homepages.csv", "r")) !== FALSE) {
    while (($columns = fgetcsv($handle, 1000, ",", '"')) !== FALSE) {
        $row++;
        if($columns[1] == "") {
            continue;
        }
        echo "Searching for ".$columns[0]."\n";
        $key = "";
        $stmt = $conn->prepare("SELECT `key` FROM `municipalities` WHERE `address_zip`=?");
        $zip = substr($columns[2],0,5);
        $stmt->bind_param('s',$zip);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows == 1) {
            $key = $result->fetch_assoc()['key'];
        }
        $stmt->close();
        if(!$key) {
            $stmt = $conn->prepare("SELECT `key` FROM `municipalities` WHERE `name`LIKE ?");
            $search = "%".$columns[0]."%";
            $stmt->bind_param('s', $search);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows == 1) {
                $key = $result->fetch_assoc()['key'];
            }
            $stmt->close();
        }
        if($key) {
            $stmt = $conn->prepare("UPDATE `municipalities` SET `website`=? WHERE `key` = ?");
            $stmt->bind_param("ss", $columns[1], $key);
            if($stmt->execute()) {
                echo "Updated $key $columns[0] $columns[1]\n";
            }
            $stmt->close();
        }
    }
}
fclose($handle);