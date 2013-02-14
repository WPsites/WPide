#!/bin/bash
set -e
set -u

ssh -o StrictHostKeyChecking=yes -o UserKnownHostsFile=$WPIDE_SSH_PATH/known_hosts -i $WPIDE_SSH_PATH/id_rsa $@
