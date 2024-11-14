## Tambahkan SSH_AUTH_SOCK ke ~/.bashrc
echo 'export SSH_AUTH_SOCK=$(gpgconf --list-dirs agent-ssh-socket)' >> ~/.bashrc

echo 'gpgconf --launch gpg-agent' >> ~/.bashrc


## Tambahkan ENABLE-SSH-SUPPORT ke ~/.gnupg/gpg-agent.conf
if ! grep -q "enable-ssh-support" ~/.gnupg/gpg-agent.conf; then

    echo 'enable-ssh-support' >> ~/.gnupg/gpg-agent.conf

fi

## gpg --full-generate-key --expert
Generate Keys with Sign, Encrypt, And Auth Capability

## Step 4
gpg --list-keys --with-keygrip

Add Keygrip to ~/.gnupg/sshcontrol

## Step 5
ssh-add -L and export public key to server and authenticate !
