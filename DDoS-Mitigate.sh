DATABASE_FILE="database.json"
NGINX_ERROR_LOG="/home/data/safeline/logs/nginx/error.log"
NGINX_BLOCKLIST_FILE="blocklist.conf"

check_dependencies() {
    for cmd in jq tail grep date; do
        if ! command -v "$cmd" &> /dev/null; then
            log_error "$cmd perlu diinstal"
            exit 1
        fi
    done
}

init_database() {
    if [ ! -f "$DATABASE_FILE" ] || [ ! -s "$DATABASE_FILE" ]; then
        touch "$DATABASE_FILE"
        echo '[]' > "$DATABASE_FILE"
    fi
}

export_to_nginx_blocklist() {
    local temp_blocklist=$(mktemp)

    jq -r '.[].ip_addr' "$DATABASE_FILE" | sort -u | while read -r ip; do
        echo "deny $ip;" >> "$temp_blocklist"
    done

    mv "$temp_blocklist" "$NGINX_BLOCKLIST_FILE"

    docker exec -it safeline-tengine nginx -s reload
}

add_to_database() {
    local ip="$1"
    local current_time=$(date "+%Y-%m-%d %H:%M:%S")

    local temp_file=$(mktemp)
    existing_entry=$(jq --arg ip "$ip" '.[] | select(.ip_addr == $ip)' "$DATABASE_FILE")

    if [ -z "$existing_entry" ]; then
        jq --arg ip "$ip" --arg created "$current_time" \
           '. + [{
               "ip_addr": $ip, 
               "created_at": $created, 
               "modify_at": $created,
               "count": 1
           }]' "$DATABASE_FILE" > "$temp_file"
        
        echo "Menambahkan IP baru: $ip pada $current_time"
    else
        jq --arg ip "$ip" --arg modified "$current_time" \
           'map(if .ip_addr == $ip then 
               .modify_at = $modified | 
               .count += 1 
           else . end)' "$DATABASE_FILE" > "$temp_file"
        
        echo "Memperbarui IP: $ip pada $current_time"
    fi

    mv "$temp_file" "$DATABASE_FILE"
    
    export_to_nginx_blocklist
}

log_error() {
    echo "[ERROR] $1" >&2
}

main() {
    check_dependencies
    init_database

    echo "Memulai Monitoring ..."

    # Log Processor
    tail -f "$NGINX_ERROR_LOG" | \
    while read -r line; do
        if [[ "$line" == *"limiting requests"* ]]; then
            ip=$(echo "$line" | grep -oP '(?<=client: )[\d\.]+')
            if [[ -n "$ip" ]] && [[ "$ip" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
                add_to_database "$ip"
            else
                log_error "Invalid IP extracted: $ip"
            fi
        fi
    done
}

trap 'echo "Script dihentikan."; exit 0' SIGINT SIGTERM

# -- Jalankan Fungsi Utama -- #
main
