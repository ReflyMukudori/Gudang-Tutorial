## Tambahkan ini ke ~/.bashrc
export SSH_AUTH_SOCK=$(gpgconf --list-dirs agent-ssh-socket)

gpgconf --launch gpg-agent

## Tambahkan ke ~/.gnupg/gpg-agent.conf
enable-ssh-support

## gpg --full-generate-key --expert
Generate Keys with Sign, Encrypt, And Auth Capability

## Step 4
gpg --list-keys --with-keygrip

Add Keygrip to ~/.gnupg/sshcontrol

## Step 5
ssh-add -L and export public key to server and authenticate !
