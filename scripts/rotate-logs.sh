#!/bin/bash
# Log Rotation Script for Fit & Brawl
# Rotates PHP error logs, application logs, and centralized logs

set -e

# Configuration
LOG_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../logs" && pwd)"
MAX_LOG_SIZE=10485760  # 10MB in bytes
KEEP_DAYS=30           # Keep logs for 30 days
COMPRESS_LOGS=true

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "========================================="
echo "Log Rotation Script"
echo "========================================="
echo "Log Directory: $LOG_DIR"
echo "Max Log Size: $(($MAX_LOG_SIZE / 1024 / 1024))MB"
echo "Keep Days: $KEEP_DAYS"
echo "========================================="

# Function to rotate a log file
rotate_log() {
    local log_file="$1"
    local log_basename=$(basename "$log_file")
    local log_dir=$(dirname "$log_file")

    if [ ! -f "$log_file" ]; then
        echo -e "${YELLOW}[SKIP]${NC} $log_basename (file not found)"
        return
    fi

    local file_size=$(stat -f%z "$log_file" 2>/dev/null || stat -c%s "$log_file" 2>/dev/null || echo "0")

    if [ "$file_size" -lt "$MAX_LOG_SIZE" ]; then
        echo -e "${GREEN}[OK]${NC} $log_basename ($(($file_size / 1024))KB - no rotation needed)"
        return
    fi

    echo -e "${YELLOW}[ROTATE]${NC} $log_basename ($(($file_size / 1024 / 1024))MB)"

    # Rotate existing numbered logs
    for i in {9..1}; do
        if [ -f "$log_dir/${log_basename}.$i.gz" ]; then
            mv "$log_dir/${log_basename}.$i.gz" "$log_dir/${log_basename}.$((i+1)).gz"
        elif [ -f "$log_dir/${log_basename}.$i" ]; then
            mv "$log_dir/${log_basename}.$i" "$log_dir/${log_basename}.$((i+1))"
        fi
    done

    # Rotate current log
    mv "$log_file" "$log_dir/${log_basename}.1"

    # Create new empty log file
    touch "$log_file"
    chmod 664 "$log_file"

    # Compress rotated log if enabled
    if [ "$COMPRESS_LOGS" = true ]; then
        gzip "$log_dir/${log_basename}.1"
        echo -e "${GREEN}[COMPRESSED]${NC} ${log_basename}.1.gz"
    fi

    echo -e "${GREEN}[SUCCESS]${NC} $log_basename rotated"
}

# Function to clean old logs
clean_old_logs() {
    echo ""
    echo "Cleaning logs older than $KEEP_DAYS days..."

    local deleted_count=0

    # Find and delete old compressed logs
    while IFS= read -r -d '' file; do
        rm -f "$file"
        deleted_count=$((deleted_count + 1))
        echo -e "${GREEN}[DELETED]${NC} $(basename "$file")"
    done < <(find "$LOG_DIR" -name "*.gz" -type f -mtime +$KEEP_DAYS -print0 2>/dev/null)

    # Find and delete old numbered logs
    while IFS= read -r -d '' file; do
        if [[ $(basename "$file") =~ \.[0-9]+$ ]]; then
            rm -f "$file"
            deleted_count=$((deleted_count + 1))
            echo -e "${GREEN}[DELETED]${NC} $(basename "$file")"
        fi
    done < <(find "$LOG_DIR" -type f -mtime +$KEEP_DAYS -print0 2>/dev/null)

    if [ $deleted_count -eq 0 ]; then
        echo -e "${GREEN}[OK]${NC} No old logs to delete"
    else
        echo -e "${GREEN}[SUCCESS]${NC} Deleted $deleted_count old log files"
    fi
}

# Main rotation process
echo ""
echo "Rotating log files..."
echo ""

# Create logs directory if it doesn't exist
mkdir -p "$LOG_DIR"

# Rotate PHP error logs
rotate_log "$LOG_DIR/php_errors.log"

# Rotate application logs (if they exist)
[ -f "$LOG_DIR/application.log" ] && rotate_log "$LOG_DIR/application.log"
[ -f "$LOG_DIR/security.log" ] && rotate_log "$LOG_DIR/security.log"
[ -f "$LOG_DIR/activity.log" ] && rotate_log "$LOG_DIR/activity.log"
[ -f "$LOG_DIR/database.log" ] && rotate_log "$LOG_DIR/database.log"
[ -f "$LOG_DIR/email.log" ] && rotate_log "$LOG_DIR/email.log"

# Clean old logs
clean_old_logs

echo ""
echo "========================================="
echo -e "${GREEN}Log rotation completed successfully!${NC}"
echo "========================================="

# Output log directory size
if command -v du &> /dev/null; then
    total_size=$(du -sh "$LOG_DIR" 2>/dev/null | cut -f1)
    echo "Total log directory size: $total_size"
fi

exit 0
