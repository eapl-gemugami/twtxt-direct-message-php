https://twtxt.dev/exts/direct-message.html

---

2. Encrypting the Message
2.1. First, Bob retrieves Alice’s public key:

```
curl https://alice.example.com/twtxt.txt | grep public_key | cut -d' ' -f4 > alice_public_key.pem
```

2.2. Calculate the share key:

2.2.1 Bob use his private key and Alice’s public key to calculate the shared key:

```
openssl pkeyutl -derive -inkey bob_private_key.pem -peerkey alice_public_key.pem -out shared_key.bin
```

2.2.2 Then, Bob encrypts the message using the shared key. The message is storage in message.enc:

```
echo -n "Hi Alice, let's meet tomorrow at 5 PM!" | openssl enc -aes-256-cbc -pbkdf2 -iter 100000 -out message.enc -pass file:shared_key.bin
```

Note: Instead of using the following command:
`base64 -w 0 < message.enc > message.enc.b64`

The parameters `-a -A` may be used to directly get the Base64 output.
See https://docs.openssl.org/3.0/man1/openssl-enc/#options
```
echo -n "Hi Alice, let's meet tomorrow at 5 PM!" | openssl enc -e -a -A -aes-256-cbc -pbkdf2 -iter 100000 -out message.enc.b64 -pass file:shared_key.bin
```

Note: I'd add `-md sha256` and `-e` to make it explicit that we are encrypting, and using sha256.
Don't rely on default values! It's confusing

### Force a salt to be used, for testing purposes

Note: The `-S` option is no compatible with the workflow as it won't insert the required prefix "Salted__"
https://stackoverflow.com/questions/72149327/openssl-3-0-2-with-custom-salt-doesnt-start-with-salted

See the note "Please note that OpenSSL 3.0..." in documentation for this command
```
echo -n "Hi Alice, let's meet tomorrow at 5 PM!" | openssl enc -e -aes-256-cbc -pbkdf2 -iter 100000 -out message.enc -pass file:shared_key.bin -salt -S 3B5A93C02570AEB2 -md sha256
```

### Print the key and IV

```
openssl enc -e -P -a -A -aes-256-cbc -pbkdf2 -iter 100000 -out message.enc.b64 -pass file:shared_key.bin -md sha256
openssl enc -e -P -a -A -aes-256-cbc -pbkdf2 -iter 100000 -out message.enc.b64 -pass file:shared_key.bin -md sha256 -salt -S 3B5A93C02570AEB2
```

Output:
```
salt=3B5A93C02570AEB2 (8 bytes)
key=C67BBF53995463E932AAF1B4A2D433BF4A589F36E63C0E19ED7DE9A47F5AF7A2 (32 bytes)
iv=1500A66CDA8D6E0B0B57CF1257203EB8 (16 bytes)
```

3. Bob encodes the encrypted message in Base64:

```
base64 -w 0 < message.enc > message.enc.b64
```

Check message.enc.b64 for the encrypted message: U2FsdGVkX1+mVLsw62BUyjcjnAVtU/EP04gS9GuTsD8xW66BH3V+kb828lMswrDntCtKgauLDZEDRCmpAo3lcQ==

This will be the string of the direct message.

## 4. Decripting the message
Alice’s client fetches the message from Bob’s feed and review if the message is for her.
She would see `!<alice https://alice.example.com/twtxt.txt>`.

4.1. Alice decodes the encrypted message from Base64:

```
echo 'U2FsdGVkX187WpPAJXCusqEoTb3/tD62xN+TxudcTsPI+LqOJLPkl9aNE9MLg8lYRLfd9mSE33N6JeA0okLJ6Q==' | base64 -d > message_from_bob.enc
```

4.2. Alice decrypts the message using the shared key.

```
openssl enc -d -aes-256-cbc -pbkdf2 -iter 100000 -in message_from_bob.enc -out message_from_bob.txt -pass file:shared_key.bin
```

4.3. Check message_from_bob.txt for the decrypted message.

```
cat message_from_bob.txt
```
