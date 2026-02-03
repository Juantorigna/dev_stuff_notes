
<?php
/*
HMac 
Two parties want to communicate, but they want to ensure the contents of their communication have not been tampered with.

Hmac is a cryptographic technique that proves: 
a) Who sent the message (authentication);
b) The message wasn't changed (integrity).

HMac does not provide confidentiality; encryption (e.g. TLS/AES) is required for privacy.

HMac uses:

1) Shared secret key: 
    A shared key needed to authenticate the sender as a trusted party. 
2) Hash function:
     A mathematical algorithm that has the role of applying a one-way cryptographic transformation to what is sent to the receiver.
     The receiver needs to apply the same method to the plain message and the key in their possession to then confront the result with the received string.
     If there is a match, the message has not been tampered with. 
     It is preferable to use an hash function since it is a deterministic, fast, and constant-time algorithm like SHA-256 or SHA-512.
     The algorithm choice for HMac is fundamentally different from that of password storing. As a matter of fact, salt and pepper are not applied in this context.  

A pair using this system has to agree on both the key and the hashing mechanism. 

It often adds little value if no untrusted boundary exists, but it can still protect against client-side tampering or forged requests.

Workflow: 
1) The sender computes the Hmac: 
    Let: 
        - M = The plain message; 
        - K = the shared key; 
        - H = the underlying hash function.

        Two keys are produced from the original: 
        Inner_key = XOR_1(K);^*
        Outer_key = XOR_2(K).

        Then:    
        tag = HMAC(K,M) where tag = H(Outer_key || H(Inner_key || M))^**

2) The receiver: 
    -Receives the tag, along with M;
    -Recomputes expected_tag(K, M) using the secret key in their possession; 
    -Checks if tag = expected_tag
    -The comparison must be done in CONSTANT TIME^***

If tag = expected_tag then M is authentic and unmodified; 
If tag != expected_tag then M is not authentic and has been modified.

HMac does not answer to the following quetions when follows the structure aferomentioned: 

    - Is this message new?
    - Is it expected now?
    - Is it appropriate for context?
    - Is it authorized for this action?

The previous questions raise the following vulnerabilities: 

    1) Replay attacks (very important). The attacker can send the exact same request just sent; 
    2) Valid BUT unintended message. HMac proves authorship, not intent;
    3) Context confusion. There must be method + path + scope included in the signature;
    4) Ordering attacks. If messages are processed in sequence, an attacker can replay or reorder them. 

To fully validate a message you must bind HMac to: 
    -Body --> Integrity; 
    -Http --> Prevents misuse; 
    -Path/action --> Prevents confusion;
    -Timestamp --> Prevents replay;
    -Nonce --> Prevents duplication; 
    -Sender ID --> Prevents cross-app reuse.

Common real-world use cases: 

    a) API Authentication. 
       Your app --> sends request to payment API --> Payment API verifies the request came from you. 

    b) Webhook Verification
       Stripe sends you a webhook --> "checkout session completed" --> your server verifies Stripe actually sent it
    
    c) Signed Cookies
       The user logs in --> the server creates a session cookie with HMac --> the user returns --> the server checks the cookie 
       hasn't been changed.
    4) File integrity
       The user downloads a software --> it comes with an HMac tag --> before installing it, verify the file wasn't corrupted or modified

Table of when to use Hmac vs alternatives

Need                               Use                            Why
_____________________________________________________________________________________________________
Verify message integrity between | HMac                         | Both parties share the same secret |
trusted parties                  |                              | key                                |
_________________________________|______________________________|____________________________________|
Verify sender identity publicly  |Digital signatures (RSA/ECDSA)|Only sender has the private key     |
_________________________________|______________________________|____________________________________|
Authentication + Authorization + |JWT                           | It contains claims, it can be      |
Expiry                           |                              | verified without a database        |
_________________________________|______________________________|____________________________________|
Password storage                 |bcrypt/Argon2                 | Intentuonally slow, includes salt  |
_________________________________|______________________________|____________________________________|

^*What is XOR? XOR is an acronym standing for "exlusive OR". It's a logical operation that aims to compare two bits to then answer if they are different. 
If they are indeed different then the result is 1, otherwise it is 0.
Why does HMAC use XOR instead of hashing?
XOR allows deterministic, length-preserving key mixing without introducing new secrets. It guarantees structural separation (ensures cryptographic separation
between the key material and the message data), and it preserves the security proof of HMac. XOR uses ipad and opad for comparison: 

    e.g.
    K      = 1011
    ipad   = 0011
    ----------------
    K ⊕ ipad = 1000

    What are opad and ipad?
    They are public costants with fixed patterns and thus the same for everyone. 
    
    Inner_key = K ⊕ ipad (apply XOR with ipad on key)
    Outer_key = K ⊕ opad (apply XOR with opad on key)

^** || stands for "concatenate the bytes in this exact order. 
^*** An operation is "constant time" if it takes the same amount of time to run, no matter what the input is.
     If an operation runs faster or slower an attacker can learn info just by measuring how long it takes to perform said operation and its input.
     This is called timing side-channel attack.     

    e.g. 
    NOT constant time(dangerous)

    compare(a, b):
    for i from 0 to len: 
        if a[i] != b[i]: 
            return false
    return true

    What happens?
    If the first character is wrong → exits immediately
    If the first 10 characters are correct → runs longer
    If all characters are correct → runs longest
    Execution time leaks information
    An attacker can guess the value one byte at a time.

    Constant time (safe)

    compare (a, b):
    for i from 0 to len: 
        result |= a[i] XOR b[i]
    return result == 0

    What happens?
    Always loops over all bytes
    Always does the same operations
    Always takes the same time
    No information leaks.
    
    Why does it matter for HMac?
    If you compare them byte by byte and stop early, an attacker can:
    Send many requests
    Measure response time
    Learn which bytes are correct
    Forge a valid HMAC

    IMPORTANT FOR FUTURE IN-DEPTH ANALYSIS: 
    Where is constant time required?

        You need constant-time behavior when handling secrets, such as:
        -HMac tag comparison
        -Password hash comparison
        -Cryptographic keys
        -Authentication tokens

        You do not need it for:
        -normal business logic
        -UI code
        -database queries

        How this is handled in real code

        Most crypto libraries provide safe comparison functions:

        -PHP: hash_equals()
        -Python: hmac.compare_digest()
        
        You should always use these, never == for secrets.
*/

//Examples 

//1

//Step 1: generate a secure key


function generateSecretKey($length = 32) {
    $secretKey = random_bytes($length)
    return base64_encode($secretKey)
}

//generate and display key
$jeyString = generateSecretKey(32);
echo "Secret key (store this securely!): " . $keyString . "\n";

//To use the key later, decode it back to bytes 
$secretKey = base64_decode($keyString)

###################################################################

//Step 2: store the key securely

//NEVER DO THIS
define('SECRET_KEY', 'my-secret-123'); 

//DO THIS: 
//Option 1: Environment variables

function getSecretKey(){
    $key = getenv('HMAC_SECRET_KEY');
    if (!$key) {
        throw new Exeption('HMAC_SECRET_KEY not found in env');
    }
    return base64_decode($key);
}
$secretKey = getSecretKey();

//Option 2: web.config files 
$secret_config_file = require '/var/secret/hmac_config.php';
$secretKey = base64_decode($secret_config_file['hmac_secret']);

//Step 3: Generate HMac
$message = "Trust me!"

function create_HMAC($secretKey, $message, $algorithm = 'sha256') {
    return hash_hmac($algorithm, $message, $secretKey);
}
$sentTag = create_HMAC($secretKey, $message);
echo $tag;

//Step 4: Verify Hmac 
$secret_config_file = require '/var/secret/hmac_config.php';
$secretKey = base64_decode($secret_config_file['hmac_secret']);

function verifyHMAC($secretKey, $message, $sentTag, $algorithm = 'sha256') {
    $expectedTag = hash_hmac($algorithm, $message, $secretKey);
    //REMEMBER TO ALWAYS USE CONSTANT-TIME COMPARISON
    return hash_equals($sentTag, $expectedTag); //here's where it produces true or false
}


$isValid = verifyHMAC($secretKey, $message, $sentTag); 

if ($isValid) {
    echo "You can trust this message!\n";
}else{
    echo "You CANNOT trust this message!\n";
}






