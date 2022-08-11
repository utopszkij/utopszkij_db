#!/bin/bash
# hasznalata ./tools/git.sh gitCommand param param ....
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_utopszkij_db
git $1 $2 $3 $4 $5
