## Tambahkan SSH_AUTH_SOCK ke ~/.bashrc
    echo 'export SSH_AUTH_SOCK=$(gpgconf --list-dirs agent-ssh-socket)' >> ~/.bashrc
    echo 'gpgconf --launch gpg-agent' >> ~/.bashrc


## Tambahkan ENABLE-SSH-SUPPORT ke ~/.gnupg/gpg-agent.conf
    if ! grep -q "enable-ssh-support" ~/.gnupg/gpg-agent.conf; then
    echo 'enable-ssh-support' >> ~/.gnupg/gpg-agent.conf
    fi


## Generate Kunci GPG
    gpg --full-generate-key --expert

Pastikan Kunci memiliki fungsi Sign, Encrypt, Authentication


## Export KEYGRIP
    gpg --list-keys --with-keygrip | grep "Keygrip =" | awk '{print $3}' | xargs -I{} sh -c 'sshcontrol_file="$HOME/.gnupg/sshcontrol"; [ ! -f "$sshcontrol_file" ] && touch "$sshcontrol_file" && chmod 600 "$sshcontrol_file"; grep -q "{}" "$sshcontrol_file" || echo "{}" >> "$sshcontrol_file" && echo "Keygrip {} telah ditambahkan ke $sshcontrol_file"'

## Export SSH-PUBKEY
    ssh-add -L > ~/.ssh/pub.key


## Export SSH-PUBKEY ke Host Dan Konek dengan ssh username@host
