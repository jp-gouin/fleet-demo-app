package com.example;

import redis.clients.jedis.Jedis;
import redis.clients.jedis.exceptions.JedisConnectionException;

public class RedisPinger {

    public static void main(String[] args) {
        // Get Redis host and port from environment variables
        // Default to "localhost" and 6379 if not provided
        String redisHost = System.getenv().getOrDefault("REDIS_HOST", "localhost");
        int redisPort = Integer.parseInt(System.getenv().getOrDefault("REDIS_PORT", "6379"));

        Jedis jedis = null;
        try {
            // Attempt to connect to Redis
            System.out.println("Attempting to connect to Redis at " + redisHost + ":" + redisPort + "...");
            jedis = new Jedis(redisHost, redisPort, 2000); 
            
            // Send a PING command to check the connection
            String response = jedis.ping();

            // If the PING is successful, the response is "PONG"
            if ("PONG".equalsIgnoreCase(response)) {
                System.out.println("Successfully connected to Redis and received PONG!");
            } else {
                System.err.println("Unexpected response from Redis: " + response);
                // Crash the application
                System.exit(1);
            }

        } catch (JedisConnectionException e) {
            // This exception is thrown if the connection fails
            System.err.println("Failed to connect to Redis: " + e.getMessage());
            e.printStackTrace();
            // Crash the application by exiting with a non-zero status code
            System.exit(1);
        } catch (NumberFormatException e) {
            System.err.println("Invalid REDIS_PORT specified. Must be an integer.");
            System.exit(1);
        } finally {
            // Always close the connection if it was opened
            if (jedis != null) {
                jedis.close();
            }
        }

        System.out.println("Application finished successfully.");
    }
}
