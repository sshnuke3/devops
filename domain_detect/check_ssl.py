import ssl
import socket
from datetime import datetime, timedelta
#检查domains.txt中ssl证书将在30天内到期的域名
# 读取域名列表
def read_domains(filename):
    with open(filename, 'r') as file:
        return [line.strip() for line in file]

# 检查SSL证书有效期
def check_ssl_expiration(domain):
    context = ssl.create_default_context()
    conn = context.wrap_socket(socket.socket(), server_hostname=domain)
    # 设置超时时间以避免挂起
    conn.settimeout(5.0)

    try:
        conn.connect((domain, 443))
        cert = conn.getpeercert()
        not_after = datetime.strptime(cert['notAfter'], "%b %d %H:%M:%S %Y %Z")
        return not_after
    except ssl.SSLError as e:
        #print(f"SSL错误：{domain}: {e}")
        pass
    except socket.timeout as e:
        #print(f"超时：{domain}: {e}")
        pass
    except ConnectionResetError as e:
        #print(f"连接被重置：{domain}: {e}")
        pass
    except socket.gaierror as e:
        #print(f"DNS解析错误：{domain}: {e}")
        pass
    except Exception as e:
        #print(f"未知错误：{domain}: {e}")
        pass
    finally:
        conn.close()

# 主函数
def main():
    domains = read_domains('domains.txt')
    thirty_days_later = datetime.now() + timedelta(days=30)

    for domain in domains:
        expiration_date = check_ssl_expiration(domain)
        if expiration_date and expiration_date <= thirty_days_later:
            days_left = (expiration_date - datetime.now()).days
            print(f"{domain}: {expiration_date}, {days_left}")

if __name__ == "__main__":
    main()
