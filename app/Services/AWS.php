<?php namespace Northstar\Services;

use Guzzle\Http\Message\RequestInterface;
use Storage;
use Aws\S3\S3Client;

class AWS
{
    /**
     * Store an image in S3.
     *
     * @param string $bucket
     * @param File $file
     */
    public function storeImage($folder, $id, $file)
    {
        if (is_string($file)) {
            $data = base64_decode($file);
            $filename = 'uploads/' . $folder . '/' . $id;
            Storage::disk('s3')->put($filename, $data);
        } else {
            $extension = $file->guessExtension();
            $filename = 'uploads/' . $folder . '/' . $id . '.' . $extension;
            Storage::disk('s3')->put($filename, file_get_contents($file));
        }

        return getenv('S3_URL') . $filename;
    }

    /**
     * @todo comment me
     */
    public function generatePresignedUrl($id)
    {
        $client = S3Client::factory(array(
            'key'    => getenv('S3_KEY'),
            'secret' => getenv('S3_SECRET'),
            'region' => 'us-east-1', // @todo does this matter?
        ));

        $command = $client->getCommand('PutObject', array(
            'Bucket' => getenv('S3_BUCKET'),
            'Key' => $id,
            'Body' => $id,
            'SourceFile' => $id
        ));
        $request = $command->prepare();

        $presignedUrl = $client->createPresignedUrl($request, "+5 minutes");

        return $presignedUrl;
    }

}
