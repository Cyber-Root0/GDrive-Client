using RestSharp;
using System;
public class Client
{
    private readonly string _bearerToken = "*B@AGNBGAGBV7896VFG098A";
    private readonly string _apiError = "Erro interno com a API.";
    private readonly RestClientOptions _restOptions = new RestClientOptions("https://melgdrive.kinghost.net/api")
    {
        RemoteCertificateValidationCallback = (sender, certificate, chain, sslPolicyErrors) => true
    };
    public async Task<List<Folder>> GetFoldersAsync()
    {
        var client = new RestClient(_restOptions);
        var request = this.createRequest("folders", Method.Get);
        try
        {
            var response = await client.ExecuteAsync<List<Folder>>(request);
            if (response.StatusCode == System.Net.HttpStatusCode.OK)
            {
                return response.Data;
            }
            throw new Exception(_apiError);
        }
        catch (Exception e)
        {
           throw new Exception(_apiError);
        }
    }
    private RestRequest createRequest(string path, Method method)
    {
        return new RestRequest(path, method).AddHeader("Authorization", $"Bearer {_bearerToken}");
    }
    public async Task<List<File>> GetFilesInFolderAsync(string folderId)
    {
        var client = new RestClient(_restOptions);
        var request = new RestRequest($"files/{folderId}", Method.Get);
        request.AddHeader("Authorization", $"Bearer {_bearerToken}");
        try
        {
            var response = await client.ExecuteAsync<List<File>>(request);
            if (response.StatusCode == System.Net.HttpStatusCode.OK)
            {
                return response.Data;
            }
            throw new Exception(_apiError);
        }
        catch (Exception e)
        {
            throw new Exception(_apiError);
        }
    }
    public async Task<UploadResponse> UploadFileAsync(string folderId, string fileName, string base64Content)
    {
        var client = new RestClient(_restOptions);
        var request = this.createRequest($"upload/{folderId}", Method.Post);
        request.AddJsonBody(new
        {
            filename =  fileName,
            content = base64Content
        });
        try
        {
            var response = await client.ExecuteAsync<UploadResponse>(request);
            if (response.StatusCode == System.Net.HttpStatusCode.OK)
            {
                return response.Data;
            }
            throw new Exception(_apiError);
        }
        catch (Exception e)
        {
            throw new Exception(_apiError);
        }

    }
    public async Task<bool> DeleteFileAsync(string fileId)
    {
        var client = new RestClient(_restOptions);
        var request = new RestRequest($"delete/{fileId}", Method.Get);
        try
        {
            var response = await client.ExecuteAsync<MessageResult>(request);
            if (response.StatusCode == System.Net.HttpStatusCode.OK && response.Data.Message == "Arquivo deletado com sucesso.")
            {
               return true;
            }
            return false;
        }
        catch (Exception e)
        {
            throw new Exception(_apiError);
        }
    }
    public class Folder
    {
        public string Name { get; set; }
        public string Id { get; set; }
    }
    public class MessageResult
    {
        public string Message { get; set; }
    }

    public class File
    {
        public string Name { get; set; }
        public string Id { get; set; }
    }

    public class UploadResponse
    {
        public string Message { get; set; }
        public string FileId { get; set; }
    }
}
