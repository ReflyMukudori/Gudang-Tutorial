## step One
export SSH_AUTH_SOCK=$(gpgconf --list-dirs agent-ssh-socket)

gpgconf --launch gpg-agent

## Step Two
Generate Keys with Sign, Encrypt, And Auth Capability

## Step Three
add enable-ssh-support to ~/.gnupg/gpg-agent.conf

## Step Four
gpg --list-keys --with-keygrip

Add Keygrip to ~/.gnupg/sshcontrol

## Step Five
ssh-add -L and export public key to server and authenticate !
