The URL which Kannel call is:
base_url/key/short_code/sender/time/smsc_id/argument


Apache notes:
#The AllowEncodedSlashes directive allows URLs which contain encoded 
#path separators (%2F for /  and additionally %5C for \ on according systems)
#to be used. Normally such URLs are refused with a 404 (Not found) error.
#Allowing encoded slashes does not imply decoding. Occurrences of %2F or %5C
#(only  on according systems) will be left as such in the otherwise decoded URL string.
AllowEncodedSlashes On

PHP notes:
Turn on short_tag

Short_code and relevant number of MT:
7027: 1
7127: 2
7227: 4
7327: 6
7427: 8
7527: 12
7627: 23
7727: 34