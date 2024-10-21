pipeline{
    agent any
    environment{
        production_server = "172.16.2.95"
    }
    stages{
        stage('Deploy to Production'){
            steps{
                sh '''
                    for fileName in `find ${WORKSPACE} -type f -mmin -10| grep -v ".git" | grep -v "Jenkinsfile"`
                    do
                        fil=$(echo ${fileName} | sed 's/'"${JOB_NAME}"'/ /' | awk {'print $2'})
                        scp ${WORKSPACE}${fil} /var/www/html/nxt.sprintnxt.in/${JOB_NAME}${fil}
                    done
                '''
            }
        }
    }
}