<?php

function deriveSharedKey($bobPrivateKeyPem, $alicePublicKeyPem) {
    $bobPrivateKey = openssl_pkey_get_private($bobPrivateKeyPem);
    $alicePublicKey = openssl_pkey_get_public($alicePublicKeyPem);

    if (!$bobPrivateKey || !$alicePublicKey) {
        throw new Exception("Invalid keys.");
    }

    # Perform ECDH key derivation
    # openssl pkeyutl -derive -inkey key.pem -peerkey pubkey.pem -out secret
    $sharedKey = openssl_pkey_derive($alicePublicKey, $bobPrivateKey);
    return bin2hex($sharedKey);
}

function encryptMessage($message, $sharedKeyHex) {
    # https://docs.openssl.org/3.0/man1/openssl-enc/#options
    # This command adds a salt. It's not clear in documentation what's the length
    # echo -n "Hi Alice, let's meet tomorrow at 5 PM!" | openssl enc -aes-256-cbc -pbkdf2 -iter 100000 -out message.enc -pass file:shared_key.bin

    # Hacking around I found this values
    # salt=3B5A93C02570AEB2 (8 bytes)
    # key=C67BBF53995463E932AAF1B4A2D433BF4A589F36E63C0E19ED7DE9A47F5AF7A2 (32 bytes)
    # iv=1500A66CDA8D6E0B0B57CF1257203EB8 (16 bytes)

    echo "Shared key: $sharedKeyHex\n";

    # $salt = openssl_random_pseudo_bytes(8);
    # DEBUG: Test with the same salt, DON'T DO THIS!
    $saltHex = '3B5A93C02570AEB2';

    # TODO: Change this into constants
    $keyLength = 48;
    $iterations = 100000;
    # https://www.php.net/manual/en/function.openssl-pbkdf2.php
    $generatedKey = openssl_pbkdf2(hex2bin($sharedKeyHex), hex2bin($saltHex), $keyLength, $iterations, 'sha256');

    # DEBUG: Hide these when you've finished
    echo "Salt: $saltHex\n";
    echo "Generated key as Hex: " . bin2hex($generatedKey) . "\n";
    echo "Generated key as Base64: " . base64_encode($generatedKey) . "\n";
    $generatedKeyHex = bin2hex($generatedKey);

    # For salt '3B5A93C02570AEB2' and Alice & Bob derivated key we expect this IV:
    # 1500A66CDA8D6E0B0B57CF1257203EB8

    # When a password is being specified using one of the other options,
    # the IV is generated from this password.
    #
    # Check this Python implementation:
    # https://crypto.stackexchange.com/a/79855

    # Split the hex representation (Passphrase is 32 bits, and IV is 16)
    $passphrase = substr($generatedKeyHex, 0, 64);
    $ivHex = substr($generatedKeyHex, 64, 32);
    $ivBin = hex2bin($ivHex);

    # DEBUG: Hide these when you've finished
    echo "Passphrase: $passphrase\nIV: $ivHex\n";

    $cipherText = openssl_encrypt(
        $message,
        'aes-256-cbc',
        $generatedKey,
        OPENSSL_RAW_DATA,
        $ivBin,
    );
    return base64_encode("Salted__" . hex2bin($saltHex) . $cipherText);
}

# Step 1: Retrieve Alice’s public key
$alicePublicKey = 'MCowBQYDK2VuAyEAvBvdsHgzmIiRL9Mjb4fVrbSQGn4Q/m9p7XZCUDj5liI=';
$alicePublicKeyPem = "-----BEGIN PUBLIC KEY-----\n$alicePublicKey\n-----END PUBLIC KEY-----";

# Step 2: Bob's private key (you should securely retrieve it)
$bobPrivateKey = 'MC4CAQAwBQYDK2VuBCIEIBDu4Sn6iaUhBrB/5XjGjZNfPDWAixDs3OCzaEt0lVpZ';
$bobPrivateKeyPem = "-----BEGIN PRIVATE KEY-----\n$bobPrivateKey\n-----END PRIVATE KEY-----";

# Step 3: Calculate the shared key
$sharedKey = deriveSharedKey($bobPrivateKeyPem, $alicePublicKeyPem);
echo "Derived shared key: $sharedKey\n";

# Step 4: Encrypt the message
$message = "Hi Alice, let's meet tomorrow at 5 PM!";
$encryptedMessage = encryptMessage($message, $sharedKey);

# Output encrypted message (Base64 encoded)
echo "Encrypted message: " . $encryptedMessage . PHP_EOL;
