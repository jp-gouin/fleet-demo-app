# Stage 1: Build the application using Maven
FROM maven:3.8.4-openjdk-11 AS build
WORKDIR /app
COPY pom.xml .
COPY src ./src
# Build the project and create the fat JAR
RUN mvn clean package

# Stage 2: Create the final, smaller runtime image
FROM openjdk:11.0.11-jre-slim-buster
WORKDIR /app
# Copy the executable JAR from the build stage
COPY --from=build /app/target/redis-pinger-1.0-SNAPSHOT-jar-with-dependencies.jar ./redis-pinger.jar
# Command to run the application
CMD ["java", "-jar", "redis-pinger.jar"]
