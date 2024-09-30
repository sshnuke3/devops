import subprocess
import json
import requests
#调用check_ssl.py检查30天内要到期的域名证书并发到钉钉
def execute_script(script_path):
    # 执行指定的Python脚本
    result = subprocess.run(["python3", script_path], capture_output=True, text=True)

    # 获取脚本的标准输出
    output = result.stdout.splitlines()

    # 返回脚本的逐行输出
    return output

def format_as_json(lines):
    # 将逐行输出转换为JSON数组
    json_array = []

    for line in lines:
        # 分割每行输出为域名、到期日期和剩余天数
        parts = line.split(': ')
        if len(parts) == 2:
            domain = parts[0]
            expiration_date, days_left = parts[1].split(', ')

            # 将每行输出作为独立的对象存储
            json_array.append({
                "domain": domain,
                "expiration_date": expiration_date,
                "days_left": int(days_left)
            })

    # 返回JSON数组
    #return json.dumps(json_array, ensure_ascii=False, indent=4)
    return json_array

def send_to_server(json_output):
    # 发送JSON数据到服务器
    url = "http://dingtalkurl"
    headers = {
        "Content-Type": "application/json"
    }

    response = requests.post(url, json=json_output, headers=headers)

    # 打印响应内容和请求数据
    print(f"响应状态码: {response.status_code}")
    print(f"响应内容: {response.text}")
    print(f"请求数据: {json.dumps(json_output, indent=4)}")

    if response.status_code == 200:
        print("数据成功发送到服务器")
    else:
        print(f"发送失败，状态码: {response.status_code}")

def main():
    # 执行指定的Python脚本
    lines = execute_script("check_ssl.py")

    # 将逐行输出格式化为JSON
    json_output = format_as_json(lines)

    # 发送JSON数据到服务器
    if json_output:
        send_to_server(json_output)

    # 输出JSON格式的结果
    #print(json_output)

if __name__ == "__main__":
    main()
