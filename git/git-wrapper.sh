#!/bin/bash
set -e
set -u

ssh -vvv -o StrictHostKeyChecking=yes -o UserKnownHostsFile=$WP_CONTENT_DIR/ssh/known_hosts -i $WP_CONTENT_DIR/ssh/id_rsa $@
