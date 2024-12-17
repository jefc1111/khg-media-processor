This is just a 'utility repo' for dealing with sets of pdfs and jpegs with poorly defined filename conventions. 

At time of writing this it selects all pdfs and all jpegs that do not appear to be part of a set. Where there appears to be a set of jpegs, it selects the jpeg which appears to be the first of the set. 

To establish whether or not a given jpeg appears to be part of a set we first check if it looks like this;  
`000001-01 - some item name (some extra detail).jpg` where `000001` is a URN and `01` is an index. We select any from the apparent set where the id is exactly "01"  
   
We also check for this kind of pattern;  
`000001 - some item name (some extra detail)(01).jpg`. In these cases we select any in the set where PHP evaluates the contents of the last set of brackets to "1".  

Usage  
1. `sudo php artisan khg:prep` and act on any relevant information provided when the script completes
2. `sudo php artisan khg:prep --write-files` 
3. zip all the pdfs and jpegs and send to the server, putting them in the folder configured in Omeka FileSideload module
4. Do a CSV upload using the CSV outputted by the above script, selecting Import Type 'Media', map URN to Media-specific data / Item / Identifier and map filename to Media source / Sideload. 
5. Once the job is complete, inspect the logs and investigate any errors

In the 'Import settings for media' page it should look like this;
Column    |	   Mappings
__________________________________________
URN       |    Item [dcterms:identifier]
filename  |    Media source [Sideload]


Notes to self;  
This requires Ghostscript (GPL Ghostscript 10.01.1) for PDF compression.  
Omeka-s media import docs are here: https://omeka.org/s/docs/user-manual/modules/csvimport/#import-media  
This thread was key to getting the CSV upload part to work https://forum.omeka.org/t/error-when-appending-data-to-existing-items-via-csv-import/13253  

