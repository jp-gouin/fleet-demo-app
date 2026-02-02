package com.example;

import redis.clients.jedis.Jedis;
import redis.clients.jedis.exceptions.JedisConnectionException;

public class RedisPinger {
    public static void main(String[] args) {
        String redisHost = System.getenv().getOrDefault("REDIS_HOST", "localhost");
        int redisPort = Integer.parseInt(System.getenv().getOrDefault("REDIS_PORT", "6379"));

        // Use a loop to keep the process alive
        while (true) {
            try (Jedis jedis = new Jedis(redisHost, redisPort, 2000)) {
                String response = jedis.ping();
                if ("PONG".equalsIgnoreCase(response)) {
                    System.out.println("Heartbeat: Redis is alive.");
                } else {
                    System.err.println("Unexpected response: " + response);
                }
            } catch (Exception e) {
                System.err.println("Redis is down: " + e.getMessage());
                // We don't System.exit(1) here anymore because we want to keep trying
            }

            try {
                // Wait 10 seconds before checking again
                Thread.sleep(10000); 
            } catch (InterruptedException e) {
                Thread.currentThread().interrupt();
                break; 
            }
        }
    }
}