<?php

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="student_template.csv"');


echo "\xEF\xBB\xBF";


echo "LRN,Last Name,Middle Initial,First Name,Birthdate (MM/DD/YYYY),Parent/Guardian,Permanent Address,Contact Number\n";


echo "123456789012,Smith,J,John,01/15/2010,Jane Smith,123 Main St,09123456789\n";

echo "\n";
echo "Note: The birthdate must be in MM/DD/YYYY format (e.g., 01/15/2010). Other accepted formats: MM-DD-YYYY, MM.DD.YYYY, or YYYY-MM-DD";

exit;
?>
