#!/usr/bin/env bash
#=================================
# Vars
#=================================

export LANG=C
export LC_ALL=C

export RESET="\e[0m"
export RED="\e[31;1m"
export GREEN="\e[32;1m"
export YELLOW="\e[33;1m"
export BLUE="\e[34;1m"
export PURPLE="\e[35;1m"
export CYAN="\e[36;1m"
export WHITE="\e[37;1m"

hostCheck="google.com"

#=================================
# Messages
#=================================

declare -A msgaction
msgaction[php85]="85,8.5.12,Update to this release or newer" 
msgaction[php84]="84,8.4.27,Update to this release or newer" 
msgaction[php83]="83,8.3.36,Update to this release or newer" 
msgaction[php82]="82,8.2.35,Update; plan a supported-branch migration" 
msgaction[php81]="81,End of life,Move to a supported branch or verify a complete vendor backport"

#=================================
# Functions
#=================================

function debug(){
    # shellcheck disable=SC2329
    function cleanup(){
        set +x
        echo -e "${WHITE}[${BLUE}DEBUG${WHITE}](${CYAN}$(date +'%T / %F')${WHITE}) ${BLUE}Finalizando depuração!${RESET}"
    }

    echo -e "${WHITE}[${BLUE}DEBUG${WHITE}](${CYAN}$(date +'%T / %F')${WHITE}) ${BLUE}Inicinado depuração!${RESET}"

    set -x

    trap cleanup EXIT
}

function __help__(){
    cat << EOF
Usage: bash php2shell-check.sh [OPTIONS]

Options:
    -h, --help               Display this help message
    -d, --debug              Enable mode debuging
    -u, --url <url>          Target URL (for 'web' mode)
    -p, --port <port>        Target port (default: standard HTTP/HTTPS)
    -m, --mod <mod>          Operation mode: 'web' (remote HTTP check) or 'local' (use local PHP CLI version check)
    -c, --cookie <string>    Cookie string to include in requests (e.g., session cookies)
    -a, --user-agent <str>   Custom User-Agent string
    -s, --ssl                Enable HTTPS/SSL connection

Examples:
    # Check a remote web target
    bash php2shell-check.sh -u example.com -p 443 -m web -s

    # Check using local PHP CLI
    bash php2shell-check.sh -m local
EOF
    exit 0
}

function waf_check(){

    function add_score(){
        score_waf=$(( score_waf + "$1" ))
    }

    local tpl_waf="waf.yaml"
    local outputfile="$PWD/outputs/${url}/site.temp"

    local check1_ok=""
    local check2_ok=""
    local check3_ok=""
    detected_waf=""

    score_waf=0

    declare -a list_fws=(cloudflare akamai imperva sucuri fastly f5_bigip aws_waf barracuda radware fortinet citrix)

    # =========================
    # CACHE (carrega tudo 1x)
    # =========================
    declare -A waf_servers
    declare -A waf_ips
    declare -A waf_cookies

    for fw in "${list_fws[@]}"; do
        waf_servers["$fw"]="$(yq -r ".wafs.name.${fw}.server" "$tpl_waf")"
        waf_ips["$fw"]="$(yq -r ".wafs.name.${fw}.ips[]" "$tpl_waf" 2>/dev/null | tr '\n' ' ')"
        waf_cookies["$fw"]="$(yq -r ".wafs.name.${fw}.ips[]" "$tpl_waf" 2>/dev/null | tr '\n' ' ')"
    done

    # ====


    # =========================
    # CHECK 1 - Servidor
    # =========================
    while read -r line; do
        serverType=$(printf '%s' "$line" | tr -d '\r' | sed 's/^[^:]*:[[:space:]]*//')

        for fw in "${list_fws[@]}"; do
            if [[ "${waf_servers[$fw]}" == "$serverType" ]]; then
                check1_ok=1
                detected_waf="$fw"
                add_score 40
            fi
        done
    done < <(grep -i '^server:' "$outputfile")

    # =========================
    # CHECK 2 - Cookie
    # =========================

    while read -r line; do
        cookie=$(echo "$line" | tr -d '\r' | cut -d ":" -f2- | xargs)
        cookie_lower="${cookie,,}"

        for fw in "${list_fws[@]}"; do
            for sig in ${waf_cookies[$fw]}; do
                if [[ "$cookie_lower" == *"${sig,,}"* ]]; then
                    check2_ok=1
                    detected_waf="$fw"
                    add_score 50
                fi
            done
        done

    done < <(grep -i "^set-cookie:" "$outputfile")

    # =========================
    # CHECK 3 - IP
    # =========================
    # shellcheck disable=SC2154
    if [[ "${ignore_icmp}" == 1 ]]; then
        echo -e "${WHITE}[${YELLOW}!${WHITE}] ${YELLOW}Ping ignored${RESET}"
        return 0
    fi

    ip=$(ping -c 1 -4 "${url}" | grep -oE '([0-9]{1,3}\.){3}[0-9]{1,3}' | head -n1)
    [[ -z "$ip" ]] && return 0

    ip_prefix=$(cut -d '.' -f1,2 <<< "$ip")

    for fw in "${list_fws[@]}"; do
        for ips in ${waf_ips[$fw]}; do
            if [[ "$ip_prefix" == "$ips" ]]; then
                check3_ok=1
                detected_waf="$fw"
                add_score 10
            fi
        done
    done

    # Saída final
    if [[ "${check1_ok:-0}" -eq 1 || "${check2_ok:-0}" -eq 1 || "${check3_ok:-0}" -eq 1 ]]; then
        echo -e "${WHITE}[${BLUE}-${WHITE}] ${CYAN}WAF: ${WHITE}${detected_waf:-NULL} | ${CYAN}Probability: ${WHITE}${score_waf}%${RESET}"
    fi
}

function __check_ssl(){

    local host="${url}"

    openssl s_client -connect "${url}":"${port}" -servername "${url}" </dev/null > /dev/null 2>&1

    status=$?

    if [[ "${status}" == 0 ]]; then
        echo -e "${WHITE}[${GREEN}+${WHITE}] ${GREEN}SSL Enabled for Host: ${WHITE}${host}${RESET}"
    else
        echo -e "${WHITE}[${RED}!${WHITE}] ${RED}Unable to establish an SSL connection!${RESET}"
    fi

}

function web(){

    outputsite=$(pwd)
    outputfile="$outputsite/outputs/$url/site.temp"

    echo -e "${GREEN}[${WHITE}*${GREEN}] Checking Connection With: ${BLUE}http://${url}:${port}${RESET}"
    if ! ping -w1 -c 1 "${url}" >/dev/null 2>&1; then
        :
    fi

    if ! nc "${url}" "${port:-443}" >/dev/null 2>&1 <<< "GET / HTTP/1.1"; then
        :
    fi

    if ! mkdir -p "${outputsite}/outputs/${url}" ;then
        :
    fi

    # Testa se o diretório existe
    if [[ -d "${outputsite}" ]]; then
        echo -e "${WHITE}[${GREEN}+${WHITE}] ${GREEN}The directory ${YELLOW}${outputsite}/$url ${GREEN}has been successfully created!${RESET}"
    else
        echo -e "${WHITE}[${RED}!${WHITE}] ${RED}The directory ${WHITE}${outputsite/$url} ${RED}does not exist!${RESET}"
        exit 1
    fi

    # Testa se está ativado o SSL
    # shellcheck disable=SC2154
    if [[ "${ssl_on:-0}" == 1 ]]; then     
        if __check_ssl; then
            curl -A "$useragent" \
            --cookie "${cookiecurl}" \
            -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" \
            -H "Accept-Language: en-US,en;q=0.5" \
            -s -I -L --max-time 10 "https://${url}:${port}" > "$outputfile"
        fi
    else
        curl -A "$useragent" \
        --cookie "${cookiecurl}" \
        -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" \
        -H "Accept-Language: en-US,en;q=0.5" \
        -s -I -L --max-time 10 "http://${url}:${port}" > "$outputfile"
    fi

    echo -e "\n${WHITE}[${GREEN}+${WHITE}] Starting Banner Capture${RESET}"
    while read -r line; do
        line=$(echo "$line" | tr -d '\r')

        if [[ "$line" =~ ^HTTP/ ]]; then
            if [[ -n "$current_version" ]]; then
                echo -e "${WHITE}[${BLUE}-${WHITE}] ${CYAN}HTTP: ${WHITE}${current_version:-NULL} ${CYAN}| Status: ${WHITE}${current_status:-NULL} ${CYAN}| Location: ${WHITE}${current_location:-NULL}${RESET}"
            fi

            current_version=$(echo "$line" | awk '{print $1}')
            current_status=$(echo "$line" | awk '{print $2}')
            current_location="NULL"

        elif [[ "$line" =~ ^[Ll]ocation: ]]; then
            current_location=$(echo "$line" | cut -d ":" -f2- | xargs)
        fi

    done < "$outputfile"

    # WAF Check
    waf_check

    # imprime o último
    if [[ -n "$current_version" ]]; then
        echo -e "${WHITE}[${BLUE}-${WHITE}] ${CYAN}HTTP: ${WHITE}${current_version:-NULL} ${CYAN}| Status: ${WHITE}${current_status:-NULL} ${CYAN}| Location: ${WHITE}${current_location:-NULL}${RESET}"
    fi

    # Identifica o tipo de servidor
    while IFS= read -r serverType; do
        echo -e "${WHITE}[${BLUE}-${WHITE}] ${CYAN}Server: ${WHITE}${serverType:-NULL}${RESET}"
    done < <(grep -i "^server:" "${outputfile}" | cut -d ' ' -f2)

    # Identifica a linguagem de programação
    while IFS= read -r langType; do
        if [ "${langType}" ]; then
            echo -e "${WHITE}[${BLUE}-${WHITE}] ${CYAN}Lang:${WHITE}${langType:-NULL}${RESET}"
            echo -e "\n${WHITE}[${GREEN}+${WHITE}] Validating the vulnerability${RESET}"

            local varfilter="${langType//PHP\//}"
            local varfilter="${varfilter#"${varfilter%%[![:space:]]*}"}"
            local phpbranch="$(echo "${varfilter//$'\r'/}"  | awk -F'.' '{print $1 $2}')"
            local phpfullver="$(echo "${varfilter//$'\r'/}" | awk -F'.' '{print $1 $2 $3}')"
            
            for msgact in "${msgaction[@]}"; do

                chkver="$(echo "${msgact}" | awk -F',' '{print $1}')"
                vercrr="$(echo "${msgact}" | awk -F',' '{print $2}')"
                chkmsg="$(echo "${msgact}" | awk -F',' '{print $3}')"

                if [[ "${phpbranch}" == "${chkver}" && "${phpfullver}" -ge "${vercrr//./}" ]]; then
                    echo -e "${GREEN}[+] Not Vulnerable!${GREEN}"
                    if ! rm -rf "${outputsite}/outputs/${url}" ; then : ;fi
                    exit 1
                fi

                if [[ "${phpbranch}" == "${chkver}" && "${phpfullver}" -le "${vercrr//./}" ]]; then
                    vercrr="PHP/$vercrr" && break
                else
                    if [[ "${phpbranch}" -le "81" ]]; then vercrr="End of Life"; fi
                fi
            done

            echo -e "${RED}[!] Vulnerable host ${WHITE}[${CYAN}http://${url}:$port${WHITE}]\n\_> ${YELLOW}Action: ${RED}PHP/${varfilter//$'\r'/} ${WHITE}-> ${GREEN}${vercrr}: ${BLUE}${chkmsg}${RESET}"

            if ! rm -rf "${outputsite}/outputs/${url}" ; then : ;fi
        fi
    done < <(grep -i "^x-powered-by:" "$outputfile" | cut -d ":" -f2-)
    
    echo -e "${RED}[!] Unable to validate server version! ${RESET}"
    if ! rm -rf "${outputsite}/outputs/${url}" ; then : ;fi

}

function localcheck(){
    
   echo -e "${GREEN}[${WHITE}*${GREEN}] Checking Connection With: ${BLUE}php://${USER:-root}@${HOSTNAME}:${PWD}/#${RESET}"

   if ! [[ -x "$(command -v php)" ]]; then
        echo "${RED}[!] PHP não está instalado!${RESET}"
        exit 1
   fi

    local phpver="$(php -v | cut -d " " -f2 | head -n1)"
    local phpbranch="$(  echo "${phpver//$'\r'/}" | awk -F'.' '{print $1 $2}')"
    local phpfullver="$( echo "${phpver//$'\r'/}" | awk -F'.' '{print $1 $2 $3}')"
    local phpini="$(php --ini)"
    local phpr="$(php -r 'printf("PHP=%s SAPI=%s\n", PHP_VERSION, PHP_SAPI);')"

    echo -e "\n${GREEN}PHP Version: ${WHITE}${phpr} ${BLUE}\n\nPaths:\n${YELLOW}${phpini}${RESET}"

    for msgact in "${msgaction[@]}"; do

        chkver="$(echo "${msgact}" | awk -F',' '{print $1}')"
        vercrr="$(echo "${msgact}" | awk -F',' '{print $2}')"
        chkmsg="$(echo "${msgact}" | awk -F',' '{print $3}')"

        if [[ "${phpbranch}" == "${chkver}" && "${phpfullver}" -ge "${vercrr//./}" ]]; then
            echo -e "${GREEN}[+] Not Vulnerable!${RESET}"
            exit 1
        fi
        
        if [[ "${phpbranch}" == "${chkver}" && "${phpfullver}" -le "${vercrr//./}" ]]; then
            vercrr="PHP/$vercrr" && break
        else
            if [[ "${phpbranch}" -le "81" ]]; then vercrr="End of Life" && break ; fi
        fi
    done

    echo -e "\n${RED}[!] Vulnerable host ${WHITE}[${CYAN}php://${USER:-root}@${HOSTNAME}:${PWD}#${WHITE}]\n\_> ${YELLOW}Action: ${RED}PHP/${phpver//$'\r'/} ${WHITE}-> ${GREEN}${vercrr}: ${BLUE}${chkmsg}${RESET}"

}

#=================================
# Banner
#=================================

cat <<BANNER
   ___  __ _____  ___  ______ ________   __      _______ _____________ __
  / _ \/ // / _ \|_  |/ __/ // / __/ /  / / ____/ ___/ // / __/ ___/ //_/
 / ___/ _  / ___/ __/_\ \/ _  / _// /__/ /_/___/ /__/ _  / _// /__/ ,<   
/_/  /_//_/_/  /____/___/_//_/___/____/____/   \___/_//_/___/\___/_/|_|  
                                                                         
By: @Pauloxc6
BANNER

#=================================
# Parser
#=================================

if [ -z "$1" ]; then __help__ ; fi

while [ $# -gt 0 ]; do
    case "$1" in
        -h|--help) __help__ ;;
        -d|--debug) debug   ;;
        -u|--url) shift ; url="$1"   ;;
        -p|--port) shift ; port="$1" ;;
        -m|--mod) shift ; mod="$1"   ;;
        -c|--cookie) shift ; cookiecurl="$1"     ;;
        -a|--user-agent) shift ; useragent="$1"  ;;
        -s|--ssl) shift ; ssl_on=1 ;;
    esac
    shift
done

#=================================
# Check Connection
#=================================

echo -e "${YELLOW}[${WHITE}*${YELLOW}] Checking internet connection!${RESET}"
if ! ping -w1 -c 1 "${hostCheck}" >/dev/null 2>&1; then
    :
fi

if ! wget -q --spider "https://${hostCheck}" >/dev/null 2>&1; then
    :
fi

#=================================
# Main
#=================================

case "${mod,,}" in
    "web") web     ;;
    "local") localcheck ;;
esac
