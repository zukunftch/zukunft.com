<?php 

/*

  import.php - select a file for importing into the zukunft.com database
  ----------


zukunft.com - calc with words

copyright 1995-2020 by zukunft.com AG, Zurich

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

if (isset($_GET['debug'])) { $debug = $_GET['debug']; } else { $debug = 0; }
include_once '../lib/zu_lib.php'; if ($debug > 0) { echo 'libs loaded<br>'; }

// open database
$db_con = zu_start("import", "", $debug);

  $result = ''; // reset the html code var
  $msg    = ''; // to collect all messages that should be shown to the user immediately

  // load the session user parameters
  $usr = New user;
  $result .= $usr->get($debug-1);
  $back = $_GET['back'];     // the word id from which this value change has been called (maybe later any page)

  // check if the user is permitted (e.g. to exclude crawlers from doing stupid stuff)
  zu_debug('import.php check user ', $debug-10);
  if ($usr->id > 0) {

    // prepare the display
    $dsp = new view_dsp;
    $dsp->id = cl(SQL_VIEW_IMPORT);
    $dsp->usr = $usr;
    $dsp->load($debug-1);

    // get the filepath of the data that are supposed to be imported
    $fileName = $_FILES["fileToUpload"]["name"];
    if ($fileName == '') {
      $fileName = $_GET['filename'];
    }

    // if the user has confirmed the upload
    zu_debug('import.php check submit ', $debug-10);
    //if ($_GET["confirm"] == 1) {
    if (isset($_POST["submit"])) {
      $uploadOk = True;
      if ($fileName <> '') {
        $msg .= 'Uploading of '.$fileName;
      }
      $imageFileType = strtolower(pathinfo($fileName,PATHINFO_EXTENSION));
      
      // Check file size if above 10 MB, which might take long
      if ($_FILES["fileToUpload"]["size"] > 11000000) {
        if ($msg == '') { $msg .= "Sorry, "; } else {$msg .= ", but "; }
        $msg .= "your file is larger than the limit of 10MB per file";
        $uploadOk = False;
      }
      if ($_FILES["fileToUpload"]["size"] <= 0) {
        if ($msg == '') { $msg .= "Sorry, "; } else {$msg .= " and "; }
        $msg .= "your file is empty";
        $uploadOk = False;
      }
      
      // Allow certain file formats
      if($imageFileType != "json") {
        if ($msg == '') { $msg .= "Sorry, "; } else {$msg .= " and "; }
        $msg .= "only JSON files are allowed at the moment";
        $uploadOk = False;
      }

      zu_debug('import.php check file '.$fileName.' done ', $debug-10);
      if ($uploadOk) {
        //checks for errors and checks that file is uploaded
        if ($_FILES['fileToUpload']['error'] == UPLOAD_ERR_OK       
            && is_uploaded_file($_FILES['fileToUpload']['tmp_name'])) {
          $json_str = file_get_contents($_FILES['fileToUpload']['tmp_name']); 
          $import = New file_import;
          $import->usr      = $usr;
          $import->json_str = $json_str;
          $import_result = $import->put($debug-1);
          if ($import_result == '') {
            $msg .= ' done ('.$import->words_done.' words, '.$import->triples_done.' triples, '.$import->formulas_done.' formulas, '.$import->sources_done.' sources, '.$import->values_done.' values, '.$import->views_done.' views loaded)';
          } else {
            $msg .= ' failed because '.$import_result.'.';
          }
        } else {
          if ($msg == '') { $msg .= "Sorry, "; } else {$msg .= " and "; }
          $msg .= "there was an error uploading your file with a size of ".$_FILES["fileToUpload"]["size"].' bytes';
        }
      }
    }
    if ($msg <> '') { $msg .= "."; }
        
    // if nothing yet done display the edit view (and any message on the top)
    if ($result == '')  {
      zu_debug('import.php display mask ', $debug-10);
      // show the value and the linked words to edit the value (again after removing or adding a word)
      $result .= $dsp->dsp_navbar($back, $debug-1);
      $result .= dsp_err($msg);
      
      $result .= dsp_form_file_select();
      // $result .= dsp_btn_text ('Start import', '/http/import.php?confirm=1&filepath='.);
      /*
      if ($fileName == '') {
        $result .= dsp_btn_text ('Start import', '/http/import.php?confirm=1');
      } else {
        $result .= dsp_btn_text ('Start import', '/http/import.php?confirm=1&filename='.$fileName);
      }
      */
    }
  }

  $result .= '<br><br>';
  $result .= btn_back($back);

  echo $result;

// Closing connection
zu_end($db_con, $debug);

?>
