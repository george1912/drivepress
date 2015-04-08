/*
 * filter to get the file contents
 */

<?php
          $file = $service->files->get($_GET['id']);
          print_r($file);
          
            
            