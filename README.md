This is just a 'utility repo' for dealing with sets of pdfs and jpegs with poorly defined filename conventions. 

At time of writing this it selects all pdfs and all jpegs that do not appear to be part of a set. Where there appears to be a set of jpegs, it selects the jpeg which appears to be the first of the set. 

To establish whether or not a given jpeg appears to be part of a set we first check if it looks like this;  
`000001-01 - some item name (some extra detail).jpg` where `000001` is a URN and `01` is an index. We select any from the apparent set where the id is exactly "01"  
We also check the last pair of brackets for this kind of pattern;  
`000001 - some item name (some extra detail)(01).jpg`. In these cases we select any where PHP evaluates the contents of the last set of brackets to "1"

To do;  
- Resizing  
- Copying to folder and zipping for transfer to server 
